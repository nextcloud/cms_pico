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

namespace OCA\CMSPico\Controller;

use OCA\CMSPico\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use Psr\Log\LoggerInterface;

trait ControllerTrait
{
	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param \Throwable $exception
	 * @param array      $data
	 *
	 * @return DataResponse
	 */
	private function createErrorResponse(\Throwable $exception, array $data = []): DataResponse
	{
		$this->logger->error($exception, [ 'app' => Application::APP_NAME, 'level' => 2 ]);

		$data['status'] = 0;
		if (\OC::$server->getSystemConfig()->getValue('debug', false)) {
			$data['exception'] = get_class($exception);
			$data['exceptionMessage'] = $exception->getMessage();
			$data['exceptionCode'] = $exception->getCode();
			$data['exceptionFile'] = $exception->getFile();
			$data['exceptionLine'] = $exception->getLine();
		}

		return new DataResponse($data, Http::STATUS_INTERNAL_SERVER_ERROR);
	}
}
