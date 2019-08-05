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
use OCA\CMSPico\Exceptions\FilesystemEncryptedException;
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Http\InternalServerErrorResponse;
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
	 * @param string $page
	 * @param bool   $proxyRequest
	 *
	 * @return Response
	 */
	public function getPage(string $site, string $page, bool $proxyRequest = false): Response
	{
		try {
			$picoPage = $this->websitesService->getPage($site, $page, $this->userId, $proxyRequest);
			return new PicoPageResponse($picoPage);
		} catch (WebsiteNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website could not be found on the server. Maybe the website was deleted?'));
		} catch (WebsiteNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t('You don\'t have access to this private website. Maybe the share was deleted or has expired?'));
		} catch (FilesystemEncryptedException $e) {
			return new NotPermittedResponse($this->l10n->t('This website is hosted on a encrypted Nextcloud instance and thus could not be accessed.'));
		} catch (ThemeNotFoundException $e) {
			return new InternalServerErrorResponse($this->l10n->t('This website uses a theme that could not be found on the server and thus could not be built.'));
		} catch (PageInvalidPathException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website page could not be found on the server. Maybe the page was deleted?'));
		} catch (PageNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website page could not be found on the server. Maybe the page was deleted?'));
		} catch (PageNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t('You don\'t have access to this website page. Maybe the share was deleted or has expired?'));
		} catch (PicoRuntimeException $e) {
			return new PicoErrorResponse($this->l10n->t('The requested website page could not be built, so that the server was unable to complete your request.'), $e);
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $site
	 * @param string $asset
	 * @param string $assetsETag
	 *
	 * @return Response
	 */
	public function getAsset(string $site, string $asset, string $assetsETag = ''): Response
	{
		try {
			$assetFile = $this->websitesService->getAsset($site, $asset, $this->userId);

			try {
				$secureMimeType = $this->mimeTypeDetector->getSecureMimeType($assetFile->getMimetype());
				return $this->createFileResponse($assetFile, (bool) $assetsETag, $secureMimeType);
			} catch (NotFoundException $e) {
				throw new AssetNotFoundException($e);
			} catch (NotPermittedException $e) {
				throw new AssetNotPermittedException($e);
			}
		} catch (WebsiteNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t('The requested website could not be found on the server. Maybe the website was deleted?'));
		} catch (WebsiteNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t('You don\'t have access to this private website. Maybe the share was deleted or has expired?'));
		} catch (FilesystemEncryptedException $e) {
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
	 * @param File        $file
	 * @param bool        $enableCache
	 * @param string|null $secureFileType
	 *
	 * @return Response
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	private function createFileResponse(File $file, bool $enableCache = true, string $secureFileType = null): Response
	{
		try {
			$etag = $file->getEtag();
		} catch (InvalidPathException $e) {
			throw new NotFoundException();
		}

		$response = new PicoFileResponse($file, $enableCache, $secureFileType);

		$clientEtag = $this->request->getHeader('If-None-Match');
		if ($etag && $clientEtag && preg_match('/^"?' . preg_quote($etag, '/') . '(?>"?$|-)/', $clientEtag)) {
			return new NotModifiedResponse($response);
		}

		return $response;
	}
}
