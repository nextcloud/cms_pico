<?php

declare(strict_types=1);

namespace OCA\CMSPico\Http;

use OCA\CMSPico\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;

class NotFoundResponse extends TemplateResponse
{
	/**
	 * NotFoundResponse constructor.
	 *
	 * @param string|null $message
	 */
	public function __construct(string $message = null)
	{
		parent::__construct(Application::APP_NAME, '404', [ 'message' => $message ], 'guest');
		$this->setStatus(Http::STATUS_NOT_FOUND);
	}
}
