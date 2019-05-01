<?php

declare(strict_types=1);

namespace OCA\CMSPico\Http;

use OCP\AppFramework\Http\TemplateResponse;

class NotPermittedResponse extends TemplateResponse
{
	/**
	 * NotPermittedResponse constructor.
	 *
	 * @param string|null $message
	 */
	public function __construct(string $message = null)
	{
		parent::__construct('core', '403', [ 'message' => $message ], 'guest');
		$this->setStatus(403);
	}
}
