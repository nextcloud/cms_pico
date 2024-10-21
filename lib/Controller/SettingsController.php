<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2017, Maxence Lange (<maxence@artificial-owl.com>)
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
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class SettingsController extends Controller
{
	use ControllerTrait;

	/** @var WebsitesService */
	private $websitesService;

	/**
	 * SettingsController constructor.
	 *
	 * @param IRequest         $request
	 * @param LoggerInterface  $logger
	 * @param WebsitesService  $websitesService
	 */
	public function __construct(IRequest $request, LoggerInterface $logger, WebsitesService $websitesService)
	{
		parent::__construct(Application::APP_NAME, $request);

		$this->logger = $logger;
		$this->websitesService = $websitesService;
	}

	/**
	 * @param array $data
	 *
	 * @return DataResponse
	 */
	public function setLimitGroups(array $data): DataResponse
	{
		try {
			if (!isset($data['limit_groups'])) {
				throw new \UnexpectedValueException();
			}

			$limitGroups = $data['limit_groups'] ? explode('|', $data['limit_groups']) : [];
			$this->websitesService->setLimitGroups($limitGroups);

			return new DataResponse();
		} catch (\Throwable $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param array $data
	 *
	 * @return DataResponse
	 */
	public function setLinkMode(array $data): DataResponse
	{
		try {
			if (!isset($data['link_mode'])) {
				throw new \UnexpectedValueException();
			}

			$this->websitesService->setLinkMode((int) $data['link_mode']);

			return new DataResponse();
		} catch (\Throwable $e) {
			return $this->createErrorResponse($e);
		}
	}
}
