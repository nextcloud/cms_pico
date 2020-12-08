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
use OCA\CMSPico\Exceptions\FilesystemNotLocalException;
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\ThemeNotCompatibleException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteInvalidFilesystemException;
use OCA\CMSPico\Exceptions\WebsiteInvalidOwnerException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Http\InternalServerErrorResponse;
use OCA\CMSPico\Http\NotFoundResponse;
use OCA\CMSPico\Http\NotModifiedResponse;
use OCA\CMSPico\Http\NotPermittedResponse;
use OCA\CMSPico\Http\PicoAssetResponse;
use OCA\CMSPico\Http\PicoErrorResponse;
use OCA\CMSPico\Http\PicoPageResponse;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

class PicoController extends Controller
{
	/** @var IUserSession */
	private $userSession;

	/** @var IL10N */
	private $l10n;

	/** @var WebsitesService */
	private $websitesService;

	/** @var FileService */
	private $fileService;

	/**
	 * PicoController constructor.
	 *
	 * @param IRequest        $request
	 * @param IUserSession    $userSession
	 * @param IL10N           $l10n
	 * @param WebsitesService $websitesService
	 * @param FileService     $fileService
	 */
	public function __construct(
		IRequest $request,
		IUserSession $userSession,
		IL10N $l10n,
		WebsitesService $websitesService,
		FileService $fileService
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userSession = $userSession;
		$this->l10n = $l10n;
		$this->websitesService = $websitesService;
		$this->fileService = $fileService;
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
			$userId = $this->userSession->getUser()->getUID();
			$picoPage = $this->websitesService->getPage($site, $page, $userId, $proxyRequest);
			return new PicoPageResponse($picoPage);
		} catch (WebsiteNotFoundException | WebsiteInvalidOwnerException $e) {
			return new NotFoundResponse($this->l10n->t(
				'The requested website could not be found on the server. Maybe the website was deleted?'
			));
		} catch (WebsiteInvalidFilesystemException $e) {
			return new InternalServerErrorResponse($this->l10n->t(
				'The file and directory structure of this website appears to be broken and thus could not be accessed.'
			));
		} catch (WebsiteNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t(
				'You don\'t have access to this private website. Maybe the share was deleted or has expired?'
			));
		} catch (FilesystemNotLocalException $e) {
			return new InternalServerErrorResponse($this->l10n->t(
				'This website is hosted on a non-local storage and thus could not be accessed.'
			));
		} catch (ThemeNotFoundException $e) {
			return new InternalServerErrorResponse($this->l10n->t(
				'This website uses a theme that could not be found on the server and thus could not be built.'
			));
		} catch (ThemeNotCompatibleException $e) {
			return new InternalServerErrorResponse($this->l10n->t(
				'This website uses a incompatible theme and thus could not be built.'
			));
		} catch (PageInvalidPathException | PageNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t(
				'The requested website page could not be found on the server. Maybe the page was deleted?'
			));
		} catch (PageNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t(
				'You don\'t have access to this website page. Maybe the share was deleted or has expired?'
			));
		} catch (PicoRuntimeException $e) {
			$errorMessage = $this->l10n->t(
				'The requested website page could not be built, so that the server was unable to complete your request.'
			);
			return new PicoErrorResponse($errorMessage, $e);
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
			$userId = $this->userSession->getUser()->getUID();
			$picoAsset = $this->websitesService->getAsset($site, $asset, $userId);

			$response = new PicoAssetResponse($picoAsset, (bool) $assetsETag);

			$assetETag = $picoAsset->getETag();
			$clientETag = $this->request->getHeader('If-None-Match');
			if ($assetETag && $clientETag) {
				if (preg_match('/^"?' . preg_quote($assetETag, '/') . '(?>"?$|-)/', $clientETag)) {
					return new NotModifiedResponse($response);
				}
			}

			return $response;
		} catch (WebsiteNotFoundException | WebsiteInvalidOwnerException $e) {
			return new NotFoundResponse($this->l10n->t(
				'The requested website could not be found on the server. Maybe the website was deleted?'
			));
		} catch (WebsiteInvalidFilesystemException $e) {
			return new InternalServerErrorResponse($this->l10n->t(
				'The file and directory structure of this website appears to be broken und thus could not be accessed.'
			));
		} catch (WebsiteNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t(
				'You don\'t have access to this private website. Maybe the share was deleted or has expired?'
			));
		} catch (FilesystemNotLocalException $e) {
			return new InternalServerErrorResponse($this->l10n->t(
				'This website is hosted on a non-local storage and thus could not be accessed.'
			));
		} catch (AssetInvalidPathException | AssetNotFoundException $e) {
			return new NotFoundResponse($this->l10n->t(
				'The requested website asset could not be found on the server. Maybe the asset was deleted?'
			));
		} catch (AssetNotPermittedException $e) {
			return new NotPermittedResponse($this->l10n->t(
				'You don\'t have access to this website asset. Maybe the share was deleted or has expired?'
			));
		}
	}
}
