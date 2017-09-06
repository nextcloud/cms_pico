<?php

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
			ConfigService::APP_TEST => $this->configService->getAppValue(
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
		return new TemplateResponse(Application::APP_NAME, 'settings.admin', [], 'blank');
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