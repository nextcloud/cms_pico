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

use OC\Encryption\Manager as EncryptionManager;
use OCA\CMSPico\Db\WebsitesRequest;
use OCA\CMSPico\Exceptions\AssetInvalidPathException;
use OCA\CMSPico\Exceptions\AssetNotFoundException;
use OCA\CMSPico\Exceptions\AssetNotPermittedException;
use OCA\CMSPico\Exceptions\FilesystemEncryptedException;
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteExistsException;
use OCA\CMSPico\Exceptions\WebsiteInvalidDataException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Model\PicoPage;
use OCA\CMSPico\Model\Website;
use OCP\Files\File;

class WebsitesService
{
	/** @var int */
	const LINK_MODE_LONG = 1;

	/** @var int */
	const LINK_MODE_SHORT = 2;

	/** @var EncryptionManager */
	private $encryptionManager;

	/** @var WebsitesRequest */
	private $websiteRequest;

	/** @var ConfigService */
	private $configService;

	/** @var TemplatesService */
	private $templatesService;

	/** @var PicoService */
	private $picoService;

	/** @var AssetsService */
	private $assetsService;

	/**
	 * WebsitesService constructor.
	 *
	 * @param WebsitesRequest  $websiteRequest
	 * @param ConfigService    $configService
	 * @param TemplatesService $templatesService
	 * @param PicoService      $picoService
	 * @param AssetsService    $assetsService
	 *
	 * @internal param Manager $encryptionManager
	 */
	public function __construct(
		WebsitesRequest $websiteRequest,
		ConfigService $configService,
		TemplatesService $templatesService,
		PicoService $picoService,
		AssetsService $assetsService
	) {
		$this->encryptionManager = \OC::$server->getEncryptionManager();
		$this->websiteRequest = $websiteRequest;
		$this->configService = $configService;
		$this->templatesService = $templatesService;
		$this->picoService = $picoService;
		$this->assetsService = $assetsService;
	}

	/**
	 * Creates a new website.
	 *
	 * @param Website $website
	 *
	 * @throws WebsiteExistsException
	 * @throws WebsiteInvalidDataException
	 * @throws ThemeNotFoundException
	 * @throws TemplateNotFoundException
	 */
	public function createWebsite(Website $website)
	{
		$website->assertValidName();
		$website->assertValidSite();
		$website->assertValidPath();
		$website->assertValidTheme();
		$website->assertValidTemplate();

		try {
			$website = $this->websiteRequest->getWebsiteFromSite($website->getSite());
			throw new WebsiteExistsException();
		} catch (WebsiteNotFoundException $e) {
			// in fact we want the website not to exist yet
		}

		$this->templatesService->installTemplates($website);
		$this->websiteRequest->create($website);
	}

	/**
	 * Updates a website.
	 *
	 * Warning: This method does not check the ownership of the website!
	 * Please use {@see Website::assertOwnedBy()} beforehand.
	 *
	 * @param Website $website
	 *
	 * @throws WebsiteNotFoundException
	 * @throws WebsiteInvalidDataException
	 * @throws ThemeNotFoundException
	 * @throws TemplateNotFoundException
	 */
	public function updateWebsite(Website $website)
	{
		$originalWebsite = $this->websiteRequest->getWebsiteFromId($website->getId());

		if ($website->getName() !== $originalWebsite->getName()) {
			$website->assertValidName();
		}
		if ($website->getSite() !== $originalWebsite->getSite()) {
			$website->assertValidSite();
		}
		if ($website->getPath() !== $originalWebsite->getPath()) {
			$website->assertValidPath();
		}
		if ($website->getTheme() !== $originalWebsite->getTheme()) {
			$website->assertValidTheme();
		}
		if ($website->getTemplateSource()) {
			if ($website->getTemplateSource() !== $originalWebsite->getTemplateSource()) {
				$website->assertValidTemplate();
			}
		}

		$this->websiteRequest->update($website);
	}

	/**
	 * Deletes a website.
	 *
	 * Warning: This method does not check the ownership of the website!
	 * Please use {@see Website::assertOwnedBy()} beforehand.
	 *
	 * @param Website $website
	 *
	 * @throws WebsiteNotFoundException
	 */
	public function deleteWebsite(Website $website)
	{
		// check whether website actually exists
		$this->websiteRequest->getWebsiteFromId($website->getId());

		$this->websiteRequest->delete($website);
	}

	/**
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
	public function getWebsiteFromId(int $siteId): Website
	{
		return $this->websiteRequest->getWebsiteFromId($siteId);
	}

	/**
	 * @param string $site
	 *
	 * @return Website
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromSite(string $site): Website
	{
		return $this->websiteRequest->getWebsiteFromSite($site);
	}

	/**
	 * @param string $userId
	 *
	 * @return Website[]
	 */
	public function getWebsitesFromUser(string $userId): array
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
	 * @throws FilesystemEncryptedException
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
		$website->setViewer($viewer ?: '');
		$website->setPage($page);

		if ($this->encryptionManager->isEnabled()) {
			throw new FilesystemEncryptedException();
		}

		return $this->picoService->getPage($website);
	}

	/**
	 * @param string      $site
	 * @param string      $asset
	 * @param string|null $viewer
	 *
	 * @return File
	 * @throws WebsiteNotFoundException
	 * @throws WebsiteNotPermittedException
	 * @throws FilesystemEncryptedException
	 * @throws AssetInvalidPathException
	 * @throws AssetNotFoundException
	 * @throws AssetNotPermittedException
	 */
	public function getAsset(string $site, string $asset, string $viewer = null): File
	{
		$website = $this->getWebsiteFromSite($site);
		$website->setViewer($viewer ?: '');
		$website->setPage($asset);

		if ($this->encryptionManager->isEnabled()) {
			throw new FilesystemEncryptedException();
		}

		return $this->assetsService->getAsset($website);
	}

	/**
	 * @param int $linkMode
	 *
	 * @throws \UnexpectedValueException
	 */
	public function setLinkMode(int $linkMode)
	{
		if (($linkMode !== self::LINK_MODE_LONG) && ($linkMode !== self::LINK_MODE_SHORT)) {
			throw new \UnexpectedValueException();
		}

		$this->configService->setAppValue(ConfigService::LINK_MODE, $linkMode);
	}

	/**
	 * @return int
	 */
	public function getLinkMode(): int
	{
		return (int) $this->configService->getAppValue(ConfigService::LINK_MODE);
	}
}
