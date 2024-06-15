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

namespace OCA\CMSPico\Model;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\ThemeNotCompatibleException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteForeignOwnerException;
use OCA\CMSPico\Exceptions\WebsiteInvalidDataException;
use OCA\CMSPico\Exceptions\WebsiteInvalidFilesystemException;
use OCA\CMSPico\Exceptions\WebsiteInvalidOwnerException;
use OCA\CMSPico\Files\StorageFolder;
use OCA\CMSPico\Files\StorageUserFolder;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use function OCA\CMSPico\t;

class Website extends WebsiteCore
{
	/** @var int */
	public const SITE_LENGTH_MIN = 3;

	/** @var int */
	public const SITE_LENGTH_MAX = 255;

	/** @var string */
	public const SITE_REGEX = '^[a-z0-9][a-z0-9_-]+[a-z0-9]$';

	/** @var int */
	public const NAME_LENGTH_MIN = 3;

	/** @var int */
	public const NAME_LENGTH_MAX = 255;

	/** @var IConfig */
	private $config;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var WebsitesService */
	private $websitesService;

	/** @var ThemesService */
	private $themesService;

	/** @var MiscService */
	private $miscService;

	/** @var StorageFolder */
	private $folder;

	/**
	 * Website constructor.
	 *
	 * @param array|null $data
	 */
	public function __construct($data = null)
	{
		$this->config = \OC::$server->getConfig();
		$this->userManager = \OC::$server->getUserManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->urlGenerator = \OC::$server->getURLGenerator();
		$this->websitesService = \OC::$server->query(WebsitesService::class);
		$this->themesService = \OC::$server->query(ThemesService::class);
		$this->miscService = \OC::$server->query(MiscService::class);

		parent::__construct($data);
	}

	/**
	 * @return string
	 */
	public function getOptionsJSON(): string
	{
		return json_encode($this->getOptions());
	}

	/**
	 * @return string
	 */
	public function getTimeZone(): string
	{
		$serverTimeZone = date_default_timezone_get() ?: 'UTC';
		return $this->config->getUserValue($this->getUserId(), 'core', 'timezone', $serverTimeZone);
	}

	/**
	 * @param string[] $groupAccess
	 *
	 * @return $this
	 */
	public function setGroupAccess(array $groupAccess): self
	{
		foreach ($groupAccess as $group) {
			if (!$this->groupManager->groupExists($group)) {
				throw new \UnexpectedValueException();
			}
		}

		$this->setOption('group_access', $groupAccess ?: null);
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getGroupAccess(): array
	{
		return $this->getOption('group_access') ?? [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getData(): array
	{
		$data = parent::getData();
		$data['timezone'] = $this->getTimeZone();
		return $data;
	}

	/**
	 * @throws WebsiteInvalidOwnerException
	 */
	public function assertValidOwner(): void
	{
		$user = $this->userManager->get($this->getUserId());
		if ($user === null) {
			throw new WebsiteInvalidOwnerException($this->getSite());
		}
		if (!$user->isEnabled()) {
			throw new WebsiteInvalidOwnerException($this->getSite());
		}
		if (!$this->websitesService->isUserAllowed($this->getUserId())) {
			throw new WebsiteInvalidOwnerException($this->getSite());
		}
	}

	/**
	 * @throws WebsiteInvalidDataException
	 */
	public function assertValidName(): void
	{
		if (strlen($this->getName()) < self::NAME_LENGTH_MIN) {
			$error = t('The name of the website must be longer.');
			throw new WebsiteInvalidDataException($this->getSite(), 'name', $error);
		}
		if (strlen($this->getName()) > self::NAME_LENGTH_MAX) {
			$error = t('The name of the website is too long.');
			throw new WebsiteInvalidDataException($this->getSite(), 'name', $error);
		}
	}

	/**
	 * @throws WebsiteInvalidDataException
	 */
	public function assertValidSite(): void
	{
		if (strlen($this->getSite()) < self::SITE_LENGTH_MIN) {
			$error = t('The identifier of the website must be longer.');
			throw new WebsiteInvalidDataException($this->getSite(), 'site', $error);
		}
		if (strlen($this->getSite()) > self::SITE_LENGTH_MAX) {
			$error = t('The identifier of the website is too long.');
			throw new WebsiteInvalidDataException($this->getSite(), 'site', $error);
		}
		if (preg_match('/' . self::SITE_REGEX . '/', $this->getSite()) !== 1) {
			$error = t('The identifier of the website can only contain lowercase alpha numeric chars.');
			throw new WebsiteInvalidDataException($this->getSite(), 'site', $error);
		}
	}

	/**
	 * @throws WebsiteInvalidDataException
	 */
	public function assertValidPath(): void
	{
		try {
			$path = $this->miscService->normalizePath($this->getPath());
			if ($path === '') {
				throw new InvalidPathException();
			}
		} catch (InvalidPathException $e) {
			$error = t('The path of the website is invalid.');
			throw new WebsiteInvalidDataException($this->getSite(), 'path', $error);
		}

		$userFolder = new StorageUserFolder($this->getUserId());

		try {
			$websiteBaseFolder = $userFolder->getFolder(dirname($path));

			try {
				$websiteFolder = $websiteBaseFolder->getFolder(basename($path));

				if (!$websiteFolder->isLocal()) {
					$error = t('The website\'s path is stored on a non-local storage.');
					throw new WebsiteInvalidDataException($this->getSite(), 'path', $error);
				}
			} catch (NotFoundException $e) {
				if (!$websiteBaseFolder->isLocal()) {
					$error = t('The website\'s path is stored on a non-local storage.');
					throw new WebsiteInvalidDataException($this->getSite(), 'path', $error);
				}
			}
		} catch (InvalidPathException | NotFoundException $e) {
			$error = t('Parent folder of the website\'s path not found.');
			throw new WebsiteInvalidDataException($this->getSite(), 'path', $error);
		}
	}

	/**
	 * @throws ThemeNotFoundException
	 * @throws ThemeNotCompatibleException
	 */
	public function assertValidTheme(): void
	{
		$this->themesService->assertValidTheme($this->getTheme());
	}

	/**
	 * @param string $userId
	 *
	 * @throws WebsiteForeignOwnerException
	 */
	public function assertOwnedBy(string $userId): void
	{
		if ($this->getUserId() !== $userId) {
			throw new WebsiteForeignOwnerException($this->getSite());
		}
	}

	/**
	 * @return StorageFolder
	 * @throws WebsiteInvalidFilesystemException
	 */
	public function getWebsiteFolder(): StorageFolder
	{
		if ($this->folder !== null) {
			try {
				// NC doesn't guarantee that mounts are present for the whole request lifetime
				// for example, if you call \OC\Files\Utils\Scanner::scan(), all mounts are reset
				// this makes OCNode instances, which rely on mounts of different users than the current, unusable
				// by calling OCFolder::get('') we can detect this situation and re-init the required mounts
				$this->folder->get('');
			} catch (\Exception $e) {
				$this->folder = null;
			}
		}

		if ($this->folder === null) {
			try {
				$userFolder = new StorageUserFolder($this->getUserId());

				$websiteFolder = $userFolder->getFolder($this->getPath());
				$this->folder = $websiteFolder->fakeRoot();
			} catch (InvalidPathException | NotFoundException $e) {
				throw new WebsiteInvalidFilesystemException($this->getSite(), $e);
			}
		}

		return $this->folder;
	}

	/**
	 * @return string
	 * @throws WebsiteInvalidFilesystemException
	 */
	public function getWebsitePath(): string
	{
		try {
			return $this->getWebsiteFolder()->getLocalPath() . '/';
		} catch (InvalidPathException | NotFoundException $e) {
			throw new WebsiteInvalidFilesystemException($this->getSite(), $e);
		}
	}

	/**
	 * @param bool $proxyRequest
	 *
	 * @return string
	 */
	public function getWebsiteUrl(bool $proxyRequest = false): string
	{
		if (!$proxyRequest) {
			$route = Application::APP_NAME . '.Pico.getPage';
			$parameters = [ 'site' => $this->getSite(), 'page' => '' ];
			return $this->urlGenerator->linkToRoute($route, $parameters) . '/';
		} else {
			return \OC::$WEBROOT . '/sites/' . urlencode($this->getSite()) . '/';
		}
	}
}
