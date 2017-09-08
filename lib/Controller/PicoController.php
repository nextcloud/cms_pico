<?php

namespace OCA\CMSPico\Controller;

use Exception;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\WebsiteDoesNotExistException;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PicoController extends Controller {


	/** @var IRequest */
	private $userId;

	/** @var WebsitesService */
	private $websitesService;

	/** @var MiscService */
	private $miscService;


	/**
	 * PicoController constructor.
	 *
	 * @param IRequest $request
	 * @param IRequest $userId
	 * @param WebsitesService $websitesService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IRequest $request, $userId, WebsitesService $websitesService, MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
		$this->websitesService = $websitesService;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $site
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return DataDisplayResponse|string
	 */
	public function getRoot($site) {

		return $this->getPage($site, '');
//		return new TemplateResponse(Application::APP_NAME, 'navigate', $data);
	}


	/**
	 * @param string $site
	 *
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return DataDisplayResponse|string
	 */
	public function getPage($site, $page) {

		try {
			$html = $this->websitesService->getWebpageFromSite($site, $page, $this->userId);
			return new DataDisplayResponse($html);

		} catch (Exception $e) {
			return $e->getMessage();
		}

	}


}