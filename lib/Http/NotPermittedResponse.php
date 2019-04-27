<?php

namespace OCA\CMSPico\Http;

use OCP\AppFramework\Http\TemplateResponse;

class NotPermittedResponse extends TemplateResponse
{
	public function __construct($message = null)
	{
		parent::__construct('core', '403', [ 'message' => $message ], 'guest');
		$this->setStatus(403);
	}
}
