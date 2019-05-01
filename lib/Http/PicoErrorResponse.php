<?php

declare(strict_types=1);

namespace OCA\CMSPico\Http;

use OCA\CMSPico\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;

class PicoErrorResponse extends TemplateResponse
{
	/** @var \Exception|null */
	private $exception;

	/**
	 * PicoErrorResponse constructor.
	 *
	 * @param string|null $message
	 * @param \Exception|null $exception
	 */
	public function __construct(string $message = null, \Exception $exception = null)
	{
		$this->exception = $exception;

		$params = [
			'message' => $message,
			'errorClass' => get_class($this->exception),
			'errorMsg' => $this->exception->getMessage(),
			'errorCode' => $this->exception->getCode(),
			'errorFile' => $this->exception->getFile(),
			'errorLine' => $this->exception->getLine(),
			'errorTrace' => $this->exception->getTraceAsString(),
			'debugMode' => \OC::$server->getSystemConfig()->getValue('debug', false),
			'remoteAddr' => \OC::$server->getRequest()->getRemoteAddress(),
			'requestID' => \OC::$server->getRequest()->getId(),
		];

		parent::__construct(Application::APP_NAME, '500', $params, 'guest');
		$this->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
	}
}
