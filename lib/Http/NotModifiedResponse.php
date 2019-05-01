<?php

declare(strict_types=1);

namespace OCA\CMSPico\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;

class NotModifiedResponse extends Response
{
	/** @var Response */
	private $originalResponse;

	/**
	 * NotModifiedResponse constructor.
	 *
	 * @param Response|null $originalResponse
	 */
	public function __construct(Response $originalResponse = null)
	{
		$this->originalResponse = $originalResponse;

		$this->setStatus(Http::STATUS_NOT_MODIFIED);

		if ($this->originalResponse) {
			// copy headers from original response (like ETag, Last-Modified, Cache-Control, ...)
			$this->setHeaders($this->originalResponse->getHeaders());
		}
	}
}
