<?php

namespace OCA\CMSPico\Service;

use OCA\CMSPico\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\ILogger;

class MiscService {

	const ALPHA = 'abcdefghijklmnopqrstuvwxyz';
	const ALPHA_NUMERIC = 'abcdefghijklmnopqrstuvwxyz0123456789';
	const ALPHA_NUMERIC_SCORES = 'abcdefghijklmnopqrstuvwxyz0123456789_-';

	/** @var ILogger */
	private $logger;

	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param string $message
	 * @param int $level
	 */
	public function log($message, $level = 2) {
		$data = array(
			'app'   => Application::APP_NAME,
			'level' => $level
		);

		$this->logger->log($level, $message, $data);
	}


	/**
	 * @param string $path
	 */
	public static function endSlash(&$path) {
		if ($path === '') {
			return;
		}

		if (substr($path, -1, 1) !== '/') {
			$path .= '/';
		}
	}


	public static function checkChars($line, $chars) {
		for ($i = 0; $i < strlen($line); $i++) {
			if (strpos($chars, substr($line, $i, 1)) === false) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	public function fail($data) {
		$this->log(json_encode($data));

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

