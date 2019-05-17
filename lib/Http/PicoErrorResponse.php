<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2019, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
