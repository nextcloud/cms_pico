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

declare(strict_types=1);

namespace OCA\CMSPico\Controller;

use Exception;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Http\NotModifiedResponse;
use OCA\CMSPico\Http\NotPermittedResponse;
use OCA\CMSPico\Http\PicoFileResponse;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\PicoService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\Files\File;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IRequest;

class PicoController extends Controller
{
	/** @var string */
	private $userId;

	/** @var WebsitesService */
	private $websitesService;

	/** @var FileService */
	private $fileService;

	/** @var IMimeTypeDetector */
	private $mimeTypeDetector;

	/**
	 * PicoController constructor.
	 *
	 * @param IRequest          $request
	 * @param string            $userId
	 * @param WebsitesService   $websitesService
	 * @param FileService       $fileService
	 * @param IMimeTypeDetector $mimeTypeDetector
	 */
	public function __construct(
		IRequest $request,
		string $userId,
		WebsitesService $websitesService,
		FileService $fileService,
		IMimeTypeDetector $mimeTypeDetector
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
		$this->websitesService = $websitesService;
		$this->fileService = $fileService;
		$this->mimeTypeDetector = $mimeTypeDetector;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $site
	 *
	 * @return Response
	 */
	public function getRoot(string $site) : Response
	{
		return $this->getPage($site, '');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $site
	 * @param string $page
	 *
	 * @return Response
	 */
	public function getPage(string $site, string $page) : Response
	{
		try {
			$html = $this->websitesService->getWebpageFromSite($site, $this->userId, $page);

			if (strpos($page, PicoService::DIR_ASSETS) === 0) {
				$probableMimeType = $this->mimeTypeDetector->detectPath($page);
				$secureMimeType = $this->mimeTypeDetector->getSecureMimeType($probableMimeType);

				return new DataDownloadResponse($html, basename($page), $secureMimeType);
			} else {
				return new DataDisplayResponse($html);
			}
		} catch (Exception $e) {
			return new DataDisplayResponse($e->getMessage());
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return Response
	 */
	public function getPlugin($file) : Response
	{
		try {
			$file = $this->fileService->getFile(PicoService::DIR_PLUGINS . '/' . $file);
			return $this->createFileResponse($file);
		} catch (NotFoundException $e) {
			return new NotFoundResponse();
		} catch (NotPermittedException $e) {
			return new NotPermittedResponse();
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return Response
	 */
	public function getTheme($file) : Response
	{
		try {
			$file = $this->fileService->getFile(PicoService::DIR_THEMES . '/' . $file);
			return $this->createFileResponse($file);
		} catch (NotFoundException $e) {
			return new NotFoundResponse();
		} catch (NotPermittedException $e) {
			return new NotPermittedResponse();
		}
	}

	/**
	 * @param File        $file
	 * @param string|null $secureFileType
	 *
	 * @return Response
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	private function createFileResponse(File $file, string $secureFileType = null) : Response
	{
		try {
			$etag = $file->getEtag();
		} catch (InvalidPathException $e) {
			throw new NotFoundException();
		}

		$response = new PicoFileResponse($file, $secureFileType);

		$clientEtag = $this->request->getHeader('If-None-Match');
		if ($etag && (preg_match('/^"?' . preg_quote($etag, '/') . '(?>"?$|-)/', $clientEtag) === 1)) {
			return new NotModifiedResponse($response);
		}

		return $response;
	}
}
