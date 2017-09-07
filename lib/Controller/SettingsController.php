<?php

namespace OCA\CMSPico\Controller;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SettingsController extends Controller {

	/** @var string */
	private $userId;

	/** @var ConfigService */
	private $configService;

	/** @var WebsitesService */
	private $websitesService;

	/** @var MiscService */
	private $miscService;


	/**
	 * NavigationController constructor.
	 *
	 * @param IRequest $request
	 * @param string $userId
	 * @param ConfigService $configService
	 * @param WebsitesService $websitesService
	 * @param MiscService $miscService
	 */
	function __construct(
		IRequest $request, $userId, ConfigService $configService, WebsitesService $websitesService,
		MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);
		$this->userId = $userId;
		$this->configService = $configService;
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
		$this->websitesService->createWebsite($this->userId, $data['website'], $data['path']);

		return new DataResponse($this->getPersonalWebsites(), Http::STATUS_OK);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getPersonalWebsites() {
		$websites = $this->websitesService->getWebsitesFromUser($this->userId);

		return new DataResponse($websites, Http::STATUS_OK);
	}


	/**
	 * @return DataResponse
	 */
	public function getSettingsAdmin() {
		$data = [
			ConfigService::APP_TEST => $this->configService->getAppValue(
				ConfigService::APP_TEST
			)
		];

		return new DataResponse($data, Http::STATUS_OK);
	}

	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	public function setSettingsAdmin($data) {
		$this->configService->setAppValue(
			ConfigService::APP_TEST, $data[ConfigService::APP_TEST]
		);

		return $this->getSettingsAdmin();
	}

}