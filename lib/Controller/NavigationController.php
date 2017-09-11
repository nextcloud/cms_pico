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

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class NavigationController extends Controller {

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * NavigationController constructor.
	 *
	 * @param IRequest $request
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	function __construct(IRequest $request, ConfigService $configService, MiscService $miscService) {
		parent::__construct(Application::APP_NAME, $request);
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return TemplateResponse
	 */
	public function navigate() {
		$data = [
			ConfigService::APP_TEST          => $this->configService->getAppValue(
				ConfigService::APP_TEST
			),
			ConfigService::APP_TEST_PERSONAL => $this->configService->getUserValue(
				ConfigService::APP_TEST_PERSONAL
			)
		];

		return new TemplateResponse(Application::APP_NAME, 'navigate', $data);
	}


	/**
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function admin() {
		$data = ['nchost' => \OC::$server->getURLGenerator()->getBaseUrl()];

		return new TemplateResponse(Application::APP_NAME, 'settings.admin', $data, 'blank');
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function personal() {
		return new TemplateResponse(Application::APP_NAME, 'settings.personal', [], 'blank');
	}


}