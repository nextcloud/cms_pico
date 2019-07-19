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

use OC\Files\View;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteForeignOwnerException;
use OCA\CMSPico\Exceptions\WebsiteInvalidDataException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\PicoService;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Service\ThemesService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;

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

	/** @var IAppManager */
	private $appManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var View */
	private $ownerView;

	/** @var ThemesService */
	private $themesService;

	/** @var TemplatesService */
	private $templatesService;

	/** @var MiscService */
	private $miscService;

	/**
	 * Website constructor.
	 *
	 * @param array|string|null $data
	 */
	public function __construct($data = null)
	{
		$this->config = \OC::$server->getConfig();
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);
		$this->appManager = \OC::$server->getAppManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->rootFolder = \OC::$server->getRootFolder();
		$this->urlGenerator = \OC::$server->getURLGenerator();
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
	 * @return string
	 */
	public function getWebsiteUrl(): string
	{
		if (!$this->getProxyRequest()) {
			$route = Application::APP_NAME . '.Pico.getRoot';
			$parameters = [ 'site' => $this->getSite() ];
			return $this->urlGenerator->linkToRoute($route, $parameters);
		} else {
			return \OC::$WEBROOT . '/sites/' . urlencode($this->getSite()) . '/';
		}
	}

	/**
	 * @return string
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsitePath(): string
	{
		$ownerView = $this->getOwnerView();
		$localPath = $ownerView->getLocalFolder('');

		if ($localPath === null) {
			throw new WebsiteNotFoundException();
		}

		return $localPath . '/';
	}

	/**
	 * @param string $path
	 * @param bool   $isFolder
	 *
	 * @return string
	 * @throws WebsiteNotFoundException
	 * @throws NotFoundException
	 */
	public function getAbsolutePath(string $path = '', bool $isFolder = true): string
	{
		$ownerView = $this->getOwnerView();
		$localPath = $isFolder ? $ownerView->getLocalFolder($path) : $ownerView->getLocalFile($path);

		if ($localPath === null) {
			throw new NotFoundException();
		}

		return $localPath . ($isFolder ? '/' : '');
	}

	/**
	 * @param string $file
	 *
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getPageFileId(string $file = ''): int
	{
		$userFolder = $this->rootFolder->getUserFolder($this->getUserId());
		$fileNode = $userFolder->get($this->getPath() . $file);
		return $fileNode->getId();
	}

	/**
	 * @param string $absolutePath
	 *
	 * @return string
	 * @throws WebsiteNotFoundException
	 * @throws PageInvalidPathException
	 * @throws PageNotFoundException
	 */
	public function getRelativePagePath(string $absolutePath): string
	{
		try {
			$contentDir = $this->getAbsolutePath(PicoService::DIR_CONTENT);
			$contentDirLength = strlen($contentDir);
			if (substr($absolutePath, 0, $contentDirLength) === $contentDir) {
				return substr($absolutePath, $contentDirLength);
			}
		} catch (NotFoundException $e) {
			throw new PageNotFoundException($e);
		}

		throw new PageInvalidPathException();
	}

	/**
	 * @param string|null $file
	 *
	 * @return string
	 * @throws WebsiteNotFoundException
	 * @throws PageInvalidPathException
	 * @throws PageNotFoundException
	 * @throws PageNotPermittedException
	 */
	public function getFileContent(string $file = null): string
	{
		try {
			try {
				$file = ($file !== null) ? $this->getRelativePagePath($file) : $this->getPage();
				$userFolder = $this->rootFolder->getUserFolder($this->getUserId());

				/** @var File $fileNode */
				$fileNode = $userFolder->get($this->getPath() . PicoService::DIR_CONTENT . '/' . $file);

				if (!($fileNode instanceof File)) {
					throw new PageNotFoundException();
				}

				try {
					return $fileNode->getContent();
				} catch (NotPermittedException $e) {
					throw new PageNotPermittedException($e);
				}
			} catch (NotFoundException $e) {
				throw new PageNotFoundException($e);
			}
		} catch (PageInvalidPathException $e) {
			$appPath = $this->appManager->getAppPath(Application::APP_NAME) . '/';
			$appPathLength = strlen($appPath);

			if (substr($file, 0, $appPathLength) !== $appPath) {
				throw new PageInvalidPathException();
			}

			$file = substr($file, $appPathLength);

			if (strpos($file, 'appdata_public/' . PicoService::DIR_THEMES . '/') !== 0) {
				if (strpos($file, 'appdata_public/' . PicoService::DIR_PLUGINS . '/') !== 0) {
					throw new PageInvalidPathException();
				}
			}

			if (!is_file($appPath . $file)) {
				throw new PageNotFoundException();
			}
			if (!is_readable($appPath . $file)) {
				throw new PageNotPermittedException();
			}

			return file_get_contents($file) ?: '';
		}
	}

	/**
	 * @param string $path
	 * @param array  $meta
	 *
	 * @throws WebsiteNotPermittedException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function assertViewerAccess(string $path, array $meta = [])
	{
		$exceptionClass = WebsiteNotPermittedException::class;
		if ($this->getOption('private') !== '1') {
			if (empty($meta['access'])) {
				return;
			}

			$groupAccess = explode(',', strtolower($meta['access']));
			foreach ($groupAccess as $group) {
				if ($group === 'public') {
					return;
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

			$viewerUserFolder = $this->rootFolder->getUserFolder($this->getViewer());
			$viewerFiles = $viewerUserFolder->getById($this->getPageFileId($path));
			foreach ($viewerFiles as $file) {
				if ($file->isReadable()) {
					return;
				}
			}
		}

		throw new $exceptionClass();
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
			throw new WebsiteInvalidDataException('site', $this->l10n->t('The identifier of the website must be longer.'));
		}
		if (strlen($this->getSite()) > self::SITE_LENGTH_MAX) {
			throw new WebsiteInvalidDataException('site', $this->l10n->t('The identifier of the website is too long.'));
		}

		if (preg_match('/' . self::SITE_REGEX . '/', $this->getSite()) !== 1) {
			throw new WebsiteInvalidDataException(
				'site',
				$this->l10n->t('The identifier of the website can only contains alpha numeric chars.')
			);
		}
	}

	/**
	 * @throws WebsiteInvalidDataException
	 */
	public function assertValidPath()
	{
		try {
			$this->miscService->normalizePath($this->getPath());
		} catch (InvalidPathException $e) {
			throw new WebsiteInvalidDataException(
				'path',
				$this->l10n->t('The path of the website is invalid.')
			);
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($this->getUserId());

			/** @var Folder $node */
			$node = $userFolder->get(dirname($this->getPath()));
			if (!($node instanceof Folder)) {
				throw new NotFoundException();
			}
		} catch (NotFoundException $e) {
			throw new WebsiteInvalidDataException(
				'path',
				$this->l10n->t('Parent folder of the website\'s path not found.')
			);
		}
	}

	/**
	 * @throws ThemeNotFoundException
	 */
	public function assertValidTheme()
	{
		$this->themesService->assertValidTheme($this->getTheme());
	}

	/**
	 * @throws TemplateNotFoundException
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
	 * @return View
	 * @throws WebsiteNotFoundException
	 */
	private function getOwnerView(): View
	{
		if ($this->ownerView === null) {
			try {
				$this->ownerView = new View($this->getUserId() . '/files/' . $this->getPath());
			} catch (\Exception $e) {
				throw new WebsiteNotFoundException();
			}
		}

		return $this->ownerView;
	}
}
