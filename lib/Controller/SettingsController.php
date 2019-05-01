<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\CMSPico\Controller;

use Exception;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class SettingsController extends Controller {

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
	 * @param IRequest $request
	 * @param string $userId
	 * @param ConfigService $configService
	 * @param TemplatesService $templatesService
	 * @param ThemesService $themesService
	 * @param WebsitesService $websitesService
	 * @param MiscService $miscService
	 */
	function __construct(
		IRequest $request, $userId, ConfigService $configService, TemplatesService $templatesService,
		ThemesService $themesService, WebsitesService $websitesService,
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
	 * @param array $data
	 *
	 * @return DataResponse
	 */
	public function createPersonalWebsite($data) {

		try {
			$this->websitesService->createWebsite(
				$data['name'], $this->userId, $data['website'], $data['path'], $data['template']
			);

			return $this->miscService->success(
				[
					'name'     => $data['name'],
					'websites' => $this->websitesService->getWebsitesFromUser($this->userId)
				]
			);
		} catch (Exception $e) {
			return $this->miscService->fail(['name' => $data['name'], 'error' => $e->getMessage()]);
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param $data
	 *
	 * @return DataResponse
	 */
	public function removePersonalWebsite($data) {

		try {
			$this->websitesService->deleteWebsite($data['id'], $this->userId);

			return $this->miscService->success(
				[
					'name'     => $data['name'],
					'websites' => $this->websitesService->getWebsitesFromUser($this->userId)
				]
			);
		} catch (Exception $e) {
			return $this->miscService->fail(['name' => $data['name'], 'error' => $e->getMessage()]);
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param int $siteId
	 * @param string $theme
	 *
	 * @return DataResponse
	 */
	public function updateWebsiteTheme($siteId, $theme) {

		try {
			$website = $this->websitesService->getWebsiteFromId($siteId);

			$website->hasToBeOwnedBy($this->userId);
			$website->setTheme((string)$theme);

			$this->themesService->hasToBeAValidTheme($theme);
			$this->websitesService->updateWebsite($website);

			return $this->miscService->success(
				['websites' => $this->websitesService->getWebsitesFromUser($this->userId)]
			);
		} catch (Exception $e) {
			return $this->miscService->fail(['error' => $e->getMessage()]);
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param int $siteId
	 * @param string $option
	 * @param string $value
	 *
	 * @return DataResponse
	 */
	public function editPersonalWebsiteOption($siteId, $option, $value) {

		try {
			$website = $this->websitesService->getWebsiteFromId($siteId);

			$website->hasToBeOwnedBy($this->userId);
			$website->setOption((string)$option, (string)$value);

			$this->websitesService->updateWebsite($website);

			return $this->miscService->success(
				['websites' => $this->websitesService->getWebsitesFromUser($this->userId)]
			);
		} catch (Exception $e) {
			return $this->miscService->fail(['error' => $e->getMessage()]);
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getPersonalWebsites() {
		try {
			$websites = $this->websitesService->getWebsitesFromUser($this->userId);

			return $this->miscService->success(
				[
					'themes'   => $this->themesService->getThemesList(),
					'websites' => $websites
				]
			);
		} catch (Exception $e) {
			return $this->miscService->fail(['error' => $e->getMessage()]);
		}
	}


	/**
	 * @return DataResponse
	 */
	public function getSettingsAdmin() {
		$data = [
			'templates'     => $this->templatesService->getTemplatesList(true),
			'templates_new' => $this->templatesService->getNewTemplatesList(),
			'themes'        => $this->themesService->getThemesList(true),
			'themes_new'    => $this->themesService->getNewThemesList()
		];

		return new DataResponse($data, Http::STATUS_OK);
	}


	/**
	 * @return DataResponse
	 */
	public function setSettingsAdmin() {
		return $this->getSettingsAdmin();
	}


	/**
	 * @param string $template
	 *
	 * @return DataResponse
	 */
	public function addCustomTemplate($template) {

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
	public function removeCustomTemplate($template) {

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
	public function addCustomTheme($theme) {

		$custom = $this->themesService->getThemesList(true);
		array_push($custom, $theme);
		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($custom));

		return $this->getSettingsAdmin();
	}

	/**
	 * @param string $theme
	 *
	 * @return DataResponse
	 */
	public function removeCustomTheme($theme) {

		$custom = $this->themesService->getThemesList(true);

		$k = array_search($theme, $custom);
		if ($k !== false) {
			unset($custom[$k]);
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($custom));

		return $this->getSettingsAdmin();
	}


	/**
	 * compat NC 12 and lower
	 *
	 * @return TemplateResponse
	 */
	public function nc12personal() {
		$data = [
			'templates' => $this->templatesService->getTemplatesList()
		];

		return new TemplateResponse(Application::APP_NAME, 'settings.personal', $data, '');
	}
}
