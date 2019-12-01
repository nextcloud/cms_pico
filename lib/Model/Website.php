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
use OCA\CMSPico\Exceptions\TemplateNotCompatibleException;
use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Exceptions\ThemeNotCompatibleException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteForeignOwnerException;
use OCA\CMSPico\Exceptions\WebsiteInvalidDataException;
use OCA\CMSPico\Exceptions\WebsiteInvalidFilesystemException;
use OCA\CMSPico\Exceptions\WebsiteInvalidOwnerException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Files\StorageFile;
use OCA\CMSPico\Files\StorageFolder;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\Files\Folder as OCFolder;
use OCP\Files\InvalidPathException;
use OCP\Files\Node as OCNode;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;

class Website extends WebsiteCore
{
	/** @var int */
	const SITE_LENGTH_MIN = 3;

	/** @var int */
	const SITE_LENGTH_MAX = 255;

	/** @var string */
	const SITE_REGEX = '^[a-z][a-z0-9_-]+[a-z0-9]$';

	/** @var int */
	const NAME_LENGTH_MIN = 3;

	/** @var int */
	const NAME_LENGTH_MAX = 255;

	/** @var IConfig */
	private $config;

	/** @var IL10N */
	private $l10n;

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

	/** @var TemplatesService */
	private $templatesService;

	/** @var MiscService */
	private $miscService;

	/** @var StorageFolder */
	private $folder;

	/**
	 * Website constructor.
	 *
	 * @param array|string|null $data
	 */
	public function __construct($data = null)
	{
		$this->config = \OC::$server->getConfig();
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);
		$this->userManager = \OC::$server->getUserManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->urlGenerator = \OC::$server->getURLGenerator();
		$this->websitesService = \OC::$server->query(WebsitesService::class);
		$this->themesService = \OC::$server->query(ThemesService::class);
		$this->templatesService = \OC::$server->query(TemplatesService::class);
		$this->miscService = \OC::$server->query(MiscService::class);

		parent::__construct($data);
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
	 * @param string $path
	 * @param array  $meta
	 *
	 * @throws InvalidPathException
	 * @throws WebsiteInvalidFilesystemException
	 * @throws WebsiteNotPermittedException
	 * @throws NotPermittedException
	 */
	public function assertViewerAccess(string $path, array $meta = [])
	{
		$exceptionClass = WebsiteNotPermittedException::class;
		if ($this->getType() === self::TYPE_PUBLIC) {
			if (empty($meta['access'])) {
				return;
			}

			$groupAccess = $meta['access'];
			if (!is_array($groupAccess)) {
				$groupAccess = explode(',', $groupAccess);
			}

			foreach ($groupAccess as $group) {
				$group = trim($group);

				if ($group === 'public') {
					return;
				} elseif ($group === 'private') {
					continue;
				}

				if ($this->getViewer() && $this->groupManager->groupExists($group)) {
					if ($this->groupManager->isInGroup($this->getViewer(), $group)) {
						return;
					}
				}
			}

			$exceptionClass = NotPermittedException::class;
		}

		if ($this->getViewer()) {
			if ($this->getViewer() === $this->getUserId()) {
				return;
			}

			/** @var OCFolder $viewerOCFolder */
			$viewerOCFolder = \OC::$server->getUserFolder($this->getViewer());
			$viewerAccessClosure = function (OCNode $node) use ($viewerOCFolder) {
				$nodeId = $node->getId();

				$viewerNodes = $viewerOCFolder->getById($nodeId);
				foreach ($viewerNodes as $viewerNode) {
					if ($viewerNode->isReadable()) {
						return true;
					}
				}

				return false;
			};

			$websiteFolder = $this->getWebsiteFolder();

			$path = $this->miscService->normalizePath($path);
			while ($path && ($path !== '.')) {
				try {
					/** @var StorageFile|StorageFolder $file */
					$file = $websiteFolder->get($path);
				} catch (NotFoundException $e) {
					$file = null;
				}

				if ($file) {
					if ($viewerAccessClosure($file->getOCNode())) {
						return;
					}

					throw new $exceptionClass();
				}

				$path = dirname($path);
			}

			if ($viewerAccessClosure($websiteFolder->getOCNode())) {
				return;
			}
		}

		throw new $exceptionClass();
	}

	/**
	 * @throws WebsiteInvalidOwnerException
	 */
	public function assertValidOwner()
	{
		$user = $this->userManager->get($this->getUserId());
		if ($user === null) {
			throw new WebsiteInvalidOwnerException();
		}
		if (!$user->isEnabled()) {
			throw new WebsiteInvalidOwnerException();
		}
		if (!$this->websitesService->isUserAllowed($this->getUserId())) {
			throw new WebsiteInvalidOwnerException();
		}
	}

	/**
	 * @throws WebsiteInvalidDataException
	 */
	public function assertValidName()
	{
		if (strlen($this->getName()) < self::NAME_LENGTH_MIN) {
			throw new WebsiteInvalidDataException('name', $this->l10n->t('The name of the website must be longer.'));
		}
		if (strlen($this->getName()) > self::NAME_LENGTH_MAX) {
			throw new WebsiteInvalidDataException('name', $this->l10n->t('The name of the website is too long.'));
		}
	}

	/**
	 * @throws WebsiteInvalidDataException
	 */
	public function assertValidSite()
	{
		if (strlen($this->getSite()) < self::SITE_LENGTH_MIN) {
			$error = $this->l10n->t('The identifier of the website must be longer.');
			throw new WebsiteInvalidDataException('site', $error);
		}
		if (strlen($this->getSite()) > self::SITE_LENGTH_MAX) {
			$error = $this->l10n->t('The identifier of the website is too long.');
			throw new WebsiteInvalidDataException('site', $error);
		}
		if (preg_match('/' . self::SITE_REGEX . '/', $this->getSite()) !== 1) {
			$error = $this->l10n->t('The identifier of the website can only contain lowercase alpha numeric chars.');
			throw new WebsiteInvalidDataException('site', $error);
		}
	}

	/**
	 * @throws WebsiteInvalidDataException
	 */
	public function assertValidPath()
	{
		try {
			$path = $this->miscService->normalizePath($this->getPath());
			if ($path === '') {
				throw new InvalidPathException();
			}
		} catch (InvalidPathException $e) {
			throw new WebsiteInvalidDataException(
				'path',
				$this->l10n->t('The path of the website is invalid.')
			);
		}

		$userFolder = new StorageFolder(\OC::$server->getUserFolder($this->getUserId()));

		try {
			$websiteBaseFolder = $userFolder->getFolder(dirname($path));

			try {
				$websiteFolder = $websiteBaseFolder->getFolder(basename($path));

				if (!$websiteFolder->isLocal()) {
					throw new WebsiteInvalidDataException(
						'path',
						$this->l10n->t('The website\'s path is stored on a non-local storage.')
					);
				}
			} catch (NotFoundException $e) {
				if (!$websiteBaseFolder->isLocal()) {
					throw new WebsiteInvalidDataException(
						'path',
						$this->l10n->t('The website\'s path is stored on a non-local storage.')
					);
				}
			}
		} catch (InvalidPathException $e) {
			throw new WebsiteInvalidDataException(
				'path',
				$this->l10n->t('Parent folder of the website\'s path not found.')
			);
		} catch (NotFoundException $e) {
			throw new WebsiteInvalidDataException(
				'path',
				$this->l10n->t('Parent folder of the website\'s path not found.')
			);
		}
	}

	/**
	 * @throws ThemeNotFoundException
	 * @throws ThemeNotCompatibleException
	 */
	public function assertValidTheme()
	{
		$this->themesService->assertValidTheme($this->getTheme());
	}

	/**
	 * @throws TemplateNotFoundException
	 * @throws TemplateNotCompatibleException
	 */
	public function assertValidTemplate()
	{
		$this->templatesService->assertValidTemplate($this->getTemplateSource());
	}

	/**
	 * @param string $userId
	 *
	 * @throws WebsiteForeignOwnerException
	 */
	public function assertOwnedBy($userId)
	{
		if ($this->getUserId() !== $userId) {
			throw new WebsiteForeignOwnerException();
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
				$ocUserFolder = \OC::$server->getUserFolder($this->getUserId());
				$userFolder = new StorageFolder($ocUserFolder);

				$websiteFolder = $userFolder->getFolder($this->getPath());
				$this->folder = $websiteFolder->fakeRoot();
			} catch (InvalidPathException $e) {
				throw new WebsiteInvalidFilesystemException($e);
			} catch (NotFoundException $e) {
				throw new WebsiteInvalidFilesystemException($e);
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
		} catch (InvalidPathException $e) {
			throw new WebsiteInvalidFilesystemException($e);
		} catch (NotFoundException $e) {
			throw new WebsiteInvalidFilesystemException($e);
		}
	}

	/**
	 * @return string
	 */
	public function getWebsiteUrl(): string
	{
		if (!$this->getProxyRequest()) {
			$route = Application::APP_NAME . '.Pico.getPage';
			$parameters = [ 'site' => $this->getSite(), 'page' => '' ];
			return $this->urlGenerator->linkToRoute($route, $parameters) . '/';
		} else {
			return \OC::$WEBROOT . '/sites/' . urlencode($this->getSite()) . '/';
		}
	}
}
