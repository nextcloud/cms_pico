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

namespace OCA\CMSPico\Service;

use Exception;
use OC\Encryption\Manager;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Db\WebsitesRequest;
use OCA\CMSPico\Exceptions\AssetInvalidPathException;
use OCA\CMSPico\Exceptions\AssetNotFoundException;
use OCA\CMSPico\Exceptions\AssetNotPermittedException;
use OCA\CMSPico\Exceptions\EncryptedFilesystemException;
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteExistsException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Model\PicoPage;
use OCA\CMSPico\Model\Website;
use OCP\Files\File;
use OCP\IL10N;
use OCP\ILogger;

class WebsitesService
{
	/** @var IL10N */
	private $l10n;

	/** @var Manager */
	private $encryptionManager;

	/** @var WebsitesRequest */
	private $websiteRequest;

	/** @var TemplatesService */
	private $templatesService;

	/** @var PicoService */
	private $picoService;

	/** @var AssetsService */
	private $assetsService;

	/** @var MiscService */
	private $miscService;

	/**
	 * WebsitesService constructor.
	 *
	 * @param IL10N            $l10n
	 * @param WebsitesRequest  $websiteRequest
	 * @param TemplatesService $templatesService
	 * @param PicoService      $picoService
	 * @param AssetsService    $assetsService
	 * @param MiscService      $miscService
	 *
	 * @internal param Manager $encryptionManager
	 */
	function __construct(
		IL10N $l10n,
		WebsitesRequest $websiteRequest,
		TemplatesService $templatesService,
		PicoService $picoService,
		AssetsService $assetsService,
		MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->encryptionManager = \OC::$server->getEncryptionManager();
		$this->websiteRequest = $websiteRequest;
		$this->templatesService = $templatesService;
		$this->picoService = $picoService;
		$this->assetsService = $assetsService;
		$this->miscService = $miscService;
	}

	/**
	 * createWebsite();
	 *
	 * create website using the templates file.
	 * We check that the template exists and that the inputs are valid.
	 *
	 * @param string $name
	 * @param string $userId
	 * @param string $site
	 * @param string $path
	 * @param string $template
	 *
	 * @throws WebsiteExistsException
	 */
	public function createWebsite($name, $userId, $site, $path, $template)
	{
		$this->templatesService->templateHasToExist($template);

		$website = new Website();
		$website->setName($name)
				->setUserId($userId)
				->setSite($site)
				->setPath($path)
				->setTemplateSource($template);

		try {
			$website->hasToBeFilledWithValidEntries();
			$website = $this->websiteRequest->getWebsiteFromSite($website->getSite());
			throw new WebsiteExistsException();
		} catch (WebsiteNotFoundException $e) {
			// In fact we want the website to not exist (yet).
		}

		$this->templatesService->installTemplates($website);
		$this->websiteRequest->create($website);
	}

	/**
	 * updateWebsite();
	 *
	 * update a Website.
	 *
	 * @param Website $website
	 */
	public function updateWebsite(Website $website)
	{
		$this->websiteRequest->update($website);
	}

	/**
	 * deleteWebsite();
	 *
	 * Delete a website regarding its Id and the userId
	 *
	 * @param int $siteId
	 * @param string $userId
	 */
	public function deleteWebsite($siteId, $userId)
	{
		$website = $this->getWebsiteFromId($siteId);
		$website->hasToBeOwnedBy($userId);

		$this->forceDeleteWebsite($website);
	}

	/**
	 * forceDeleteWebsite();
	 *
	 * delete a website.
	 *
	 * Warning: this method does not check the ownership of the website.
	 * Please use deleteWebsite().
	 *
	 * @param Website $website
	 */
	public function forceDeleteWebsite(Website $website)
	{
		$this->websiteRequest->delete($website);
	}

	/**
	 * Event onUserRemoved();
	 *
	 * Delete all website from the removed user.
	 *
	 * @param string $userId
	 */
	public function onUserRemoved($userId)
	{
		$this->websiteRequest->deleteAllFromUser($userId);
	}

	/**
	 * @param int $siteId
	 *
	 * @return Website
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromId($siteId): Website
	{
		return $this->websiteRequest->getWebsiteFromId($siteId);
	}

	/**
	 * @param string $site
	 *
	 * @return Website
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromSite($site): Website
	{
		return $this->websiteRequest->getWebsiteFromSite($site);
	}

	/**
	 * @param string $userId
	 *
	 * @return Website[]
	 */
	public function getWebsitesFromUser($userId): array
	{
		return $this->websiteRequest->getWebsitesFromUserId($userId);
	}

	/**
	 * @param string      $site
	 * @param string      $page
	 * @param string|null $viewer
	 * @param bool        $proxyRequest
	 *
	 * @return PicoPage
	 * @throws WebsiteNotFoundException
	 * @throws WebsiteNotPermittedException
	 * @throws EncryptedFilesystemException
	 * @throws PageInvalidPathException
	 * @throws PageNotFoundException
	 * @throws PageNotPermittedException
	 * @throws ThemeNotFoundException
	 * @throws PicoRuntimeException
	 */
	public function getPage(string $site, string $page, string $viewer = null, bool $proxyRequest = false): PicoPage
	{
		$website = $this->getWebsiteFromSite($site);
		$website->setProxyRequest($proxyRequest);
		$website->setViewer($viewer);
		$website->setPage($page);

		if ($this->encryptionManager->isEnabled()) {
			throw new EncryptedFilesystemException();
		}

		return $this->picoService->getPage($website);
	}

	/**
	 * @param string $site
	 * @param string $viewer
	 * @param string $asset
	 *
	 * @return File
	 * @throws WebsiteNotFoundException
	 * @throws WebsiteNotPermittedException
	 * @throws EncryptedFilesystemException
	 * @throws AssetInvalidPathException
	 * @throws AssetNotFoundException
	 * @throws AssetNotPermittedException
	 */
	public function getAsset(string $site, string $viewer, string $asset): File
	{
		$website = $this->getWebsiteFromSite($site);
		$website->setViewer($viewer);
		$website->setPage($asset);

		if ($this->encryptionManager->isEnabled()) {
			throw new EncryptedFilesystemException();
		}

		return $this->assetsService->getAsset($website);
	}
}
