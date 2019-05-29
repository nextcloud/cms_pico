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
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SettingsController extends Controller
{
	/** @var string */
	private $userId;

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
	 * @param ConfigService    $configService
	 * @param TemplatesService $templatesService
	 * @param ThemesService    $themesService
	 * @param WebsitesService  $websitesService
	 * @param MiscService      $miscService
	 */
	function __construct(
		IRequest $request,
		$userId,
		ConfigService $configService,
		TemplatesService $templatesService,
		ThemesService $themesService,
		WebsitesService $websitesService,
		MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
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
			$this->websitesService->createWebsite(
				$data['name'], $this->userId, $data['website'], $data['path'], $data['template']
			);

			return $this->miscService->success(
				[
					'name' => $data['name'],
					'websites' => $this->websitesService->getWebsitesFromUser($this->userId),
				]
			);
		} catch (\Exception $e) {
			return $this->miscService->fail(['name' => $data['name'], 'error' => $e->getMessage()]);
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
			$this->websitesService->deleteWebsite($data['id'], $this->userId);

			return $this->miscService->success(
				[
					'name' => $data['name'],
					'websites' => $this->websitesService->getWebsitesFromUser($this->userId),
				]
			);
		} catch (\Exception $e) {
			return $this->miscService->fail(['name' => $data['name'], 'error' => $e->getMessage()]);
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

			$website->hasToBeOwnedBy($this->userId);
			$website->setTheme($theme);

			$this->themesService->assertValidTheme($theme);
			$this->websitesService->updateWebsite($website);

			return $this->miscService->success(
				['websites' => $this->websitesService->getWebsitesFromUser($this->userId)]
			);
		} catch (\Exception $e) {
			return $this->miscService->fail(['error' => $e->getMessage()]);
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

			$website->hasToBeOwnedBy($this->userId);
			$website->setOption($option, $value);

			$this->websitesService->updateWebsite($website);

			return $this->miscService->success(
				['websites' => $this->websitesService->getWebsitesFromUser($this->userId)]
			);
		} catch (\Exception $e) {
			return $this->miscService->fail(['error' => $e->getMessage()]);
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

			return $this->miscService->success(
				[
					'themes' => $this->themesService->getThemes(),
					'websites' => $websites,
				]
			);
		} catch (\Exception $e) {
			return $this->miscService->fail(['error' => $e->getMessage()]);
		}
	}

	/**
	 * @return DataResponse
	 */
	public function getSettingsAdmin(): DataResponse
	{
		$data = [
			'templates' => $this->templatesService->getTemplatesList(true),
			'templates_new' => $this->templatesService->getNewTemplatesList(),
			'themes' => $this->themesService->getCustomThemes(),
			'themes_new' => $this->themesService->getNewCustomThemes(),
		];

		return new DataResponse($data, Http::STATUS_OK);
	}

	/**
	 * @param string $template
	 *
	 * @return DataResponse
	 */
	public function addCustomTemplate($template): DataResponse
	{
		$custom = $this->templatesService->getTemplatesList(true);
		array_push($custom, $template);
		$this->configService->setAppValue(ConfigService::CUSTOM_TEMPLATES, json_encode($custom));

		return $this->getSettingsAdmin();
	}

	/**
	 * @param string $template
	 *
	 * @return DataResponse
	 */
	public function removeCustomTemplate($template): DataResponse
	{
		$custom = $this->templatesService->getTemplatesList(true);

		$k = array_search($template, $custom);
		if ($k !== false) {
			unset($custom[$k]);
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_TEMPLATES, json_encode($custom));

		return $this->getSettingsAdmin();
	}

	/**
	 * @param string $theme
	 *
	 * @return DataResponse
	 */
	public function addCustomTheme(string $theme): DataResponse
	{
		$this->themesService->publishCustomTheme($theme);

		$customThemes = $this->themesService->getCustomThemes();
		$customThemes[] = $theme;

		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($customThemes));

		return $this->getSettingsAdmin();
	}

	/**
	 * @param string $theme
	 *
	 * @return DataResponse
	 */
	public function removeCustomTheme(string $theme): DataResponse
	{
		$customThemes = $this->themesService->getCustomThemes();

		$newCustomThemes = [];
		foreach ($customThemes as $customTheme) {
			if ($customTheme === $theme) {
				$this->themesService->depublishCustomTheme($theme);
				continue;
			}

			$newCustomThemes[] = $customTheme;
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($newCustomThemes));

		return $this->getSettingsAdmin();
	}
}
