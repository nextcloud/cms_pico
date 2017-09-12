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
use OCA\CMSPico\Service\TemplatesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

class NavigationController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ConfigService */
	private $configService;

	/** @var TemplatesService */
	private $templatesService;

	/** @var MiscService */
	private $miscService;


	/**
	 * NavigationController constructor.
	 *
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param ConfigService $configService
	 * @param TemplatesService $templatesService
	 * @param MiscService $miscService
	 */
	function __construct(
		IRequest $request, IURLGenerator $urlGenerator, ConfigService $configService,
		TemplatesService $templatesService, MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);
		$this->urlGenerator = $urlGenerator;
		$this->configService = $configService;
		$this->templatesService = $templatesService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function admin() {
		$data = [
			'nchost' => $this->urlGenerator->getBaseUrl(),
			'templates_new' => $this->templatesService->getNewTemplatesList()
		];

		return new TemplateResponse(Application::APP_NAME, 'settings.admin', $data, 'blank');
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function personal() {
		$data = [
			'templates' => $this->templatesService->getTemplatesList()
		];
		\OC::$server->getLogger()
					->log(4, '____' . json_encode($data));

		return new TemplateResponse(Application::APP_NAME, 'settings.personal', $data, 'blank');
	}


}