<?php

namespace OCA\CMSPico\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SimpleController extends Controller {

	public function __construct(IRequest $request) {
		parent::__construct(Application::APP_NAME, $request);
	}


	/**
	 * @param $param1
	 * @param $param2
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function entry($param1, $param2) {
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