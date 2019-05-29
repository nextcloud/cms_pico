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
use OCA\CMSPico\Exceptions\AssetInvalidPathException;
use OCA\CMSPico\Exceptions\AssetNotFoundException;
use OCA\CMSPico\Exceptions\AssetNotPermittedException;
use OCA\CMSPico\Exceptions\EncryptedFilesystemException;
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Http\NotFoundResponse;
use OCA\CMSPico\Http\NotModifiedResponse;
use OCA\CMSPico\Http\NotPermittedResponse;
use OCA\CMSPico\Http\PicoErrorResponse;
use OCA\CMSPico\Http\PicoFileResponse;
use OCA\CMSPico\Http\PicoPageResponse;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\PicoService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\Files\File;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IRequest;

class PicoController extends Controller
{
	/** @var string|null */
	private $userId;

	/** @var WebsitesService */
	private $websitesService;

	/** @var FileService */
	private $fileService;

	/** @var IL10N */
	private $l10n;

	/** @var IMimeTypeDetector */
	private $mimeTypeDetector;

	/**
	 * PicoController constructor.
	 *
	 * @param IRequest          $request
	 * @param string|null       $userId
	 * @param WebsitesService   $websitesService
	 * @param FileService       $fileService
	 * @param IL10N             $l10n
	 * @param IMimeTypeDetector $mimeTypeDetector
	 */
	public function __construct(
		IRequest $request,
		$userId,
		WebsitesService $websitesService,
		FileService $fileService,
		IL10N $l10n,
		IMimeTypeDetector $mimeTypeDetector
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
		$this->websitesService = $websitesService;
		$this->fileService = $fileService;
		$this->l10n = $l10n;
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
	public function getRoot(string $site): Response
	{
		return $this->getPage($site, '');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $site
	 *
	 * @return Response
	 */
	public function getRootProxy(string $site): Response
	{
		return $this->getPage($site, '', true);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $site
	 * @param string $page
	 * @param bool   $proxyRequest
	 *
	 * @return Response
	 */
	public function getPage(string $site, string $page, bool $proxyRequest = false): Response
	{
		if (strpos($page, PicoService::DIR_ASSETS . '/') === 0) {
			return $this->getAsset($site, $page);
		}

		try {
			$page = $this->websitesService->getPage($site, $page, $this->userId, $proxyRequest);
			return new PicoPageResponse($page);
		} catch (WebsiteNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website could not be found on the server. Maybe the website was deleted?'));
		} catch (WebsiteNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t('You don\'t have access to this private website. Maybe the share was deleted or has expired?'));
		} catch (EncryptedFilesystemException $e) {
			return new NotPermittedResponse($this->l10n->t('This website is hosted on a encrypted Nextcloud instance and thus could not be accessed.'));
		} catch (ThemeNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t('This website uses a theme that could not be found on the server and thus could not be built.'));
		} catch (PageInvalidPathException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website page could not be found on the server. Maybe the page was deleted?'));
		} catch (PageNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website page could not be found on the server. Maybe the page was deleted?'));
		} catch (PageNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t('You don\'t have access to this website page. Maybe the share was deleted or has expired?'));
		} catch (PicoRuntimeException $e) {
			return new PicoErrorResponse($this->l10n->t('The requested website page could not be built, so that the server was unable to complete your request.', $e));
		}
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
	public function getPageProxy(string $site, string $page): Response
	{
		return $this->getPage($site, $page, true);
	}

	/**
	 * @param string $site
	 * @param string $page
	 *
	 * @return Response
	 */
	private function getAsset(string $site, string $page): Response
	{
		try {
			$asset = $this->websitesService->getAsset($site, $this->userId, $page);

			try {
				$secureMimeType = $this->mimeTypeDetector->getSecureMimeType($asset->getMimetype());
				return $this->createFileResponse($asset, $secureMimeType);
			} catch (NotFoundException $e) {
				throw new AssetNotFoundException($e);
			} catch (NotPermittedException $e) {
				throw new AssetNotPermittedException($e);
			}
		} catch (WebsiteNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website could not be found on the server. Maybe the website was deleted?'));
		} catch (WebsiteNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t('You don\'t have access to this private website. Maybe the share was deleted or has expired?'));
		} catch (EncryptedFilesystemException $e) {
			return new NotPermittedResponse($this->l10n->t('This website is hosted on a encrypted Nextcloud instance and thus could not be accessed.'));
		} catch (AssetInvalidPathException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website asset could not be found on the server. Maybe the asset was deleted?'));
		} catch (AssetNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website asset could not be found on the server. Maybe the asset was deleted?'));
		} catch (AssetNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t('You don\'t have access to this website asset. Maybe the share was deleted or has expired?'));
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return Response
	 */
	public function getPlugin($file): Response
	{
		try {
			$file = $this->fileService->getFile(PicoService::DIR_PLUGINS . '/' . $file);
			return $this->createFileResponse($file);
		} catch (NotFoundException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website asset could not be found on the server. Maybe the asset was deleted?'));
		} catch (NotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t('You don\'t have access to this website asset. Maybe the share was deleted or has expired?'));
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
	private function createFileResponse(File $file, string $secureFileType = null): Response
	{
		try {
			$etag = $file->getEtag();
		} catch (InvalidPathException $e) {
			throw new NotFoundException();
		}

		$response = new PicoFileResponse($file, $secureFileType);

		$clientEtag = $this->request->getHeader('If-None-Match');
		if ($etag && $clientEtag && preg_match('/^"?' . preg_quote($etag, '/') . '(?>"?$|-)/', $clientEtag)) {
			return new NotModifiedResponse($response);
		}

		return $response;
	}
}
