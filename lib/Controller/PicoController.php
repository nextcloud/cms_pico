<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\CMSPico\Controller;

use Exception;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IRequest;

class PicoController extends Controller {


	/** @var IRequest */
	private $userId;

	/** @var WebsitesService */
	private $websitesService;

	/** @var MiscService */
	private $miscService;


	/**
	 * PicoController constructor.
	 *
	 * @param IRequest $request
	 * @param IRequest $userId
	 * @param WebsitesService $websitesService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IRequest $request, $userId, WebsitesService $websitesService, MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
		$this->websitesService = $websitesService;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $site
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return DataDisplayResponse|string
	 */
	public function getRoot($site) {
		return $this->getPage($site , '');
	}


	/**
	 * @param string $site
	 * @param $page
	 *
	 * @return DataDisplayResponse|string
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function getPage($site, $page) {

		try {
			$html = $this->websitesService->getWebpageFromSite($site, $this->userId, $page);

			return new DataDisplayResponse($html);

		} catch (Exception $e) {
			return $e->getMessage();
		}
	}


}