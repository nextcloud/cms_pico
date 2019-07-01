<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2017, Maxence Lange (<maxence@artificial-owl.com>)
 * @copyright Copyright (c) 2019, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace OCA\CMSPico\Controller;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\ILogger;
use OCP\IRequest;

class SettingsController extends Controller
{
	/** @var string */
	private $userId;

	/** @var ILogger */
	private $logger;

	/** @var ConfigService */
	private $configService;

	/** @var TemplatesService */
	private $templatesService;

	/** @var ThemesService */
	private $themesService;

	/** @var WebsitesService */
	private $websitesService;

	/** @var MiscService */
	private $miscService;

	/**
	 * SettingsController constructor.
	 *
	 * @param IRequest         $request
	 * @param string           $userId
	 * @param ILogger          $logger
	 * @param ConfigService    $configService
	 * @param TemplatesService $templatesService
	 * @param ThemesService    $themesService
	 * @param WebsitesService  $websitesService
	 * @param MiscService      $miscService
	 */
	function __construct(
		IRequest $request,
		$userId,
		ILogger $logger,
		ConfigService $configService,
		TemplatesService $templatesService,
		ThemesService $themesService,
		WebsitesService $websitesService,
		MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
		$this->logger = $logger;
		$this->configService = $configService;
		$this->templatesService = $templatesService;
		$this->themesService = $themesService;
		$this->websitesService = $websitesService;
		$this->miscService = $miscService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param array<string,string> $data
	 *
	 * @return DataResponse
	 */
	public function createPersonalWebsite(array $data): DataResponse
	{
		try {
			$website = (new Website())
				->setName($data['name'])
				->setUserId($this->userId)
				->setSite($data['website'])
				->setPath($data['path'])
				->setTemplateSource($data['template']);

			$this->websitesService->createWebsite($website);

			return $this->createSuccessResponse([
				'name' => $data['name'],
				'websites' => $this->websitesService->getWebsitesFromUser($this->userId),
			]);
		} catch (\Exception $e) {
			return $this->createErrorResponse([ 'name' => $data['name'], 'error' => $e->getMessage() ]);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param array<string,string> $data
	 *
	 * @return DataResponse
	 */
	public function removePersonalWebsite(array $data): DataResponse
	{
		try {
			$website = $this->websitesService->getWebsiteFromId($data['id']);

			$website->assertOwnedBy($this->userId);

			$this->websitesService->deleteWebsite($website);

			return $this->createSuccessResponse([
				'name' => $data['name'],
				'websites' => $this->websitesService->getWebsitesFromUser($this->userId),
			]);
		} catch (\Exception $e) {
			return $this->createErrorResponse([ 'name' => $data['name'], 'error' => $e->getMessage() ]);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int    $siteId
	 * @param string $theme
	 *
	 * @return DataResponse
	 */
	public function updateWebsiteTheme(int $siteId, string $theme): DataResponse
	{
		try {
			$website = $this->websitesService->getWebsiteFromId($siteId);

			$website->assertOwnedBy($this->userId);
			$website->setTheme($theme);

			$this->websitesService->updateWebsite($website);

			return $this->createSuccessResponse([
				'websites' => $this->websitesService->getWebsitesFromUser($this->userId)
			]);
		} catch (\Exception $e) {
			return $this->createErrorResponse([ 'error' => $e->getMessage() ]);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int    $siteId
	 * @param string $option
	 * @param string $value
	 *
	 * @return DataResponse
	 */
	public function editPersonalWebsiteOption(int $siteId, string $option, string $value): DataResponse
	{
		try {
			$website = $this->websitesService->getWebsiteFromId($siteId);

			$website->assertOwnedBy($this->userId);
			$website->setOption($option, $value);

			$this->websitesService->updateWebsite($website);

			return $this->createSuccessResponse([
				'websites' => $this->websitesService->getWebsitesFromUser($this->userId)
			]);
		} catch (\Exception $e) {
			return $this->createErrorResponse([ 'error' => $e->getMessage() ]);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getPersonalWebsites(): DataResponse
	{
		try {
			$websites = $this->websitesService->getWebsitesFromUser($this->userId);

			return $this->createSuccessResponse([
				'themes' => $this->themesService->getThemes(),
				'websites' => $websites,
			]);
		} catch (\Exception $e) {
			return $this->createErrorResponse([ 'error' => $e->getMessage() ]);
		}
	}

	/**
	 * @return DataResponse
	 */
	public function getTemplates(): DataResponse
	{
		$data = [
			'systemItems' => $this->templatesService->getSystemTemplates(),
			'customItems' => $this->templatesService->getCustomTemplates(),
			'newItems' => $this->templatesService->getNewCustomTemplates(),
		];

		return new DataResponse($data, Http::STATUS_OK);
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function addCustomTemplate(string $item): DataResponse
	{
		$customTemplates = $this->templatesService->getCustomTemplates();
		$customTemplates[] = $item;

		$this->configService->setAppValue(ConfigService::CUSTOM_TEMPLATES, json_encode($customTemplates));

		return $this->getTemplates();
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function removeCustomTemplate(string $item): DataResponse
	{
		$customTemplates = $this->templatesService->getCustomTemplates();

		$newCustomTemplates = [];
		foreach ($customTemplates as $customTemplate) {
			if ($customTemplate === $item) {
				continue;
			}

			$newCustomTemplates[] = $customTemplate;
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_TEMPLATES, json_encode($newCustomTemplates));

		return $this->getTemplates();
	}

	/**
	 * @return DataResponse
	 */
	public function getThemes(): DataResponse
	{
		$data = [
			'systemItems' => $this->themesService->getSystemThemes(),
			'customItems' => $this->themesService->getCustomThemes(),
			'newItems' => $this->themesService->getNewCustomThemes(),
		];

		return new DataResponse($data, Http::STATUS_OK);
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function addCustomTheme(string $item): DataResponse
	{
		$this->themesService->publishCustomTheme($item);

		$customThemes = $this->themesService->getCustomThemes();
		$customThemes[] = $item;

		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($customThemes));

		return $this->getThemes();
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function removeCustomTheme(string $item): DataResponse
	{
		$customThemes = $this->themesService->getCustomThemes();

		$newCustomThemes = [];
		foreach ($customThemes as $customTheme) {
			if ($customTheme === $item) {
				$this->themesService->depublishCustomTheme($item);
				continue;
			}

			$newCustomThemes[] = $customTheme;
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($newCustomThemes));

		return $this->getThemes();
	}

	/**
	 * @param array $data
	 *
	 * @return DataResponse
	 */
	private function createSuccessResponse(array $data): DataResponse
	{
		return new DataResponse(
			array_merge($data, [ 'status' => 1 ]),
			Http::STATUS_CREATED
		);
	}

	/**
	 * @param array $data
	 *
	 * @return DataResponse
	 */
	private function createErrorResponse(array $data): DataResponse
	{
		$this->logger->log(2, $data['message'] ?? '', [ 'app' => Application::APP_NAME ]);

		return new DataResponse(
			array_merge($data, [ 'status' => 0 ]),
			Http::STATUS_NON_AUTHORATIVE_INFORMATION
		);
	}
}
