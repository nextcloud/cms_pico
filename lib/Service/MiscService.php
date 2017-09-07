<?php

namespace OCA\CMSPico\Service;

use OCA\CMSPico\AppInfo\Application;
use OCP\ILogger;

class MiscService {

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
}

