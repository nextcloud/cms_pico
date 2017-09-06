<?php

namespace OCA\CMSPico\Controller;

use OCA\CMSPico\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class PicoController extends Controller {

	public function __construct(IRequest $request) {
		parent::__construct(Application::APP_NAME, $request);
	}


	/**
	 * @param string $site
	 *
	 * @return DataResponse
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function getRoot($site) {
		\OC::$server->getLogger()
					->log(2, '_____' . $site);

		return $this->success(['value' => 42]);
	}


	/**
	 * @param string $site
	 *
	 * @return DataResponse
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function getPage($site, $page) {
		\OC::$server->getLogger()
					->log(2, '_____' . $site . '  > ' . $page);

		return $this->success(['value' => 42]);
	}


	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	public function fail($data) {
		return new DataResponse(
			array_merge($data, array('status' => 0)),
			Http::STATUS_NON_AUTHORATIVE_INFORMATION
		);
	}

	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	public function success($data) {
		return new DataResponse(
			array_merge($data, array('status' => 1)),
			Http::STATUS_CREATED
		);
	}


}