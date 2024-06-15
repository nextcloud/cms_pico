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

use OCA\CMSPico\Db\WebsitesRequest;
use OCA\CMSPico\Exceptions\AssetInvalidPathException;
use OCA\CMSPico\Exceptions\AssetNotFoundException;
use OCA\CMSPico\Exceptions\AssetNotPermittedException;
use OCA\CMSPico\Exceptions\FilesystemNotLocalException;
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\TemplateNotCompatibleException;
use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Exceptions\ThemeNotCompatibleException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteAlreadyExistsException;
use OCA\CMSPico\Exceptions\WebsiteInvalidDataException;
use OCA\CMSPico\Exceptions\WebsiteInvalidFilesystemException;
use OCA\CMSPico\Exceptions\WebsiteInvalidOwnerException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Model\PicoAsset;
use OCA\CMSPico\Model\PicoPage;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Model\WebsiteRequest;
use OCP\Files\InvalidPathException;
use OCP\IGroupManager;

class WebsitesService
{
	/** @var int */
	public const LINK_MODE_LONG = 1;

	/** @var int */
	public const LINK_MODE_SHORT = 2;

	/** @var WebsitesRequest */
	private $websitesRequest;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ConfigService */
	private $configService;

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
	 * @param WebsitesRequest  $websiteRequest
	 * @param IGroupManager    $groupManager
	 * @param ConfigService    $configService
	 * @param TemplatesService $templatesService
	 * @param PicoService      $picoService
	 * @param AssetsService    $assetsService
	 * @param MiscService      $miscService
	 */
	public function __construct(
		WebsitesRequest $websiteRequest,
		IGroupManager $groupManager,
		ConfigService $configService,
		TemplatesService $templatesService,
		PicoService $picoService,
		AssetsService $assetsService,
		MiscService $miscService
	) {
		$this->websitesRequest = $websiteRequest;
		$this->groupManager = $groupManager;
		$this->configService = $configService;
		$this->templatesService = $templatesService;
		$this->picoService = $picoService;
		$this->assetsService = $assetsService;
		$this->miscService = $miscService;
	}

	/**
	 * Creates a new website.
	 *
	 * Warning: This method does not check whether the user is allowed to create websites!
	 * Please use {@see Website::assertValidOwner()} beforehand.
	 *
	 * @param Website $website
	 * @param string  $templateName
	 *
	 * @throws WebsiteAlreadyExistsException
	 * @throws WebsiteInvalidDataException
	 * @throws WebsiteInvalidOwnerException
	 * @throws ThemeNotFoundException
	 * @throws ThemeNotCompatibleException
	 * @throws TemplateNotFoundException
	 * @throws TemplateNotCompatibleException
	 */
	public function createWebsite(Website $website, string $templateName): void
	{
		$website->assertValidName();
		$website->assertValidSite();
		$website->assertValidPath();
		$website->assertValidTheme();

		try {
			$this->websitesRequest->getWebsiteFromSite($website->getSite());
			throw new WebsiteAlreadyExistsException($website->getSite());
		} catch (WebsiteNotFoundException $e) {
			// in fact we want the website not to exist yet
		}

		$this->templatesService->assertValidTemplate($templateName);
		$this->templatesService->installTemplate($website, $templateName);

		$this->websitesRequest->create($website);
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
	 * @throws ThemeNotCompatibleException
	 * @throws TemplateNotFoundException
	 * @throws TemplateNotCompatibleException
	 */
	public function updateWebsite(Website $website): void
	{
		$originalWebsite = $this->websitesRequest->getWebsiteFromId($website->getId());

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

		$this->websitesRequest->update($website);
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
	public function deleteWebsite(Website $website): void
	{
		// check whether website actually exists
		$this->websitesRequest->getWebsiteFromId($website->getId());

		$this->websitesRequest->delete($website);
	}

	/**
	 * Deletes all websites of a user.
	 *
	 * Warning: This method does not check the ownership of the website!
	 * Please use {@see Website::assertOwnedBy()} beforehand.
	 *
	 * @param string $userId
	 */
	public function deleteUserWebsites(string $userId): void
	{
		$this->websitesRequest->deleteAllFromUserId($userId);
	}

	/**
	 * @param int $siteId
	 *
	 * @return Website
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromId(int $siteId): Website
	{
		return $this->websitesRequest->getWebsiteFromId($siteId);
	}

	/**
	 * @param string $site
	 *
	 * @return Website
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromSite(string $site): Website
	{
		return $this->websitesRequest->getWebsiteFromSite($site);
	}

	/**
	 * @param string $userId
	 *
	 * @return Website[]
	 */
	public function getWebsitesFromUser(string $userId): array
	{
		return $this->websitesRequest->getWebsitesFromUserId($userId);
	}

	/**
	 * @return Website[]
	 */
	public function getWebsites(): array
	{
		return $this->websitesRequest->getWebsites();
	}

	/**
	 * @param string      $site
	 * @param string      $page
	 * @param string|null $viewer
	 * @param bool        $proxyRequest
	 *
	 * @return PicoPage
	 * @throws WebsiteNotFoundException
	 * @throws WebsiteInvalidOwnerException
	 * @throws WebsiteInvalidFilesystemException
	 * @throws WebsiteNotPermittedException
	 * @throws FilesystemNotLocalException
	 * @throws PageInvalidPathException
	 * @throws PageNotFoundException
	 * @throws PageNotPermittedException
	 * @throws ThemeNotFoundException
	 * @throws ThemeNotCompatibleException
	 * @throws PicoRuntimeException
	 */
	public function getPage(string $site, string $page, ?string $viewer, bool $proxyRequest = false): PicoPage
	{
		try {
			$page = $this->miscService->normalizePath($page);
		} catch (InvalidPathException $e) {
			throw new PageInvalidPathException($site, $page, $e);
		}

		$website = $this->getWebsiteFromSite($site);
		$website->assertValidOwner();

		if (!$website->getWebsiteFolder()->isLocal()) {
			throw new FilesystemNotLocalException();
		}

		$websiteRequest = new WebsiteRequest($website, $viewer, $page, $proxyRequest);
		return $this->picoService->getPage($websiteRequest);
	}

	/**
	 * @param string      $site
	 * @param string      $asset
	 * @param string|null $viewer
	 *
	 * @return PicoAsset
	 * @throws WebsiteNotFoundException
	 * @throws WebsiteInvalidOwnerException
	 * @throws WebsiteInvalidFilesystemException
	 * @throws WebsiteNotPermittedException
	 * @throws FilesystemNotLocalException
	 * @throws AssetInvalidPathException
	 * @throws AssetNotFoundException
	 * @throws AssetNotPermittedException
	 */
	public function getAsset(string $site, string $asset, ?string $viewer): PicoAsset
	{
		try {
			$asset = $this->miscService->normalizePath($asset);
			if ($asset === '') {
				throw new InvalidPathException();
			}
		} catch (InvalidPathException $e) {
			throw new AssetInvalidPathException($site, $asset, $e);
		}

		$website = $this->getWebsiteFromSite($site);
		$website->assertValidOwner();

		if (!$website->getWebsiteFolder()->isLocal()) {
			throw new FilesystemNotLocalException();
		}

		$websiteRequest = new WebsiteRequest($website, $viewer, PicoService::DIR_ASSETS . '/' . $asset);
		return $this->assetsService->getAsset($websiteRequest);
	}

	/**
	 * @param string[] $limitGroups
	 *
	 * @throws \UnexpectedValueException
	 */
	public function setLimitGroups(array $limitGroups): void
	{
		foreach ($limitGroups as $group) {
			if (!$this->groupManager->groupExists($group)) {
				throw new \UnexpectedValueException();
			}
		}

		$this->configService->setAppValue(ConfigService::LIMIT_GROUPS, json_encode($limitGroups));
	}

	/**
	 * @return string[]
	 */
	public function getLimitGroups(): array
	{
		$json = $this->configService->getAppValue(ConfigService::LIMIT_GROUPS);
		return $json ? json_decode($json, true) : [];
	}

	/**
	 * @param string|null $userId
	 *
	 * @return bool
	 */
	public function isUserAllowed(?string $userId): bool
	{
		if (!$userId) {
			return false;
		}

		$limitGroups = $this->getLimitGroups();
		if (empty($limitGroups)) {
			return true;
		}

		foreach ($limitGroups as $groupId) {
			if ($this->groupManager->isInGroup($userId, $groupId)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $linkMode
	 *
	 * @throws \UnexpectedValueException
	 */
	public function setLinkMode(int $linkMode): void
	{
		if (($linkMode !== self::LINK_MODE_LONG) && ($linkMode !== self::LINK_MODE_SHORT)) {
			throw new \UnexpectedValueException();
		}

		$this->configService->setAppValue(ConfigService::LINK_MODE, (string) $linkMode);
	}

	/**
	 * @return int
	 */
	public function getLinkMode(): int
	{
		return (int) $this->configService->getAppValue(ConfigService::LINK_MODE);
	}
}
