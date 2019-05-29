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
use OCA\CMSPico\Exceptions\CheckCharsException;
use OCA\CMSPico\Exceptions\MinCharsException;
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\PathContainSpecificFoldersException;
use OCA\CMSPico\Exceptions\UserIsNotOwnerException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\PicoService;
use OCP\App\IAppManager;
use OCP\Files\File;
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
	const TYPE_PUBLIC = 1;
	const TYPE_PRIVATE = 2;

	const SITE_LENGTH_MIN = 3;
	const NAME_LENGTH_MIN = 3;

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
	private $URLGenerator;

	/** @var View */
	private $ownerView;

	/**
	 * Website constructor.
	 *
	 * @param string $data
	 */
	public function __construct(string $data = '')
	{
		$this->config = \OC::$server->getConfig();
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);
		$this->appManager = \OC::$server->getAppManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->rootFolder = \OC::$server->getRootFolder();
		$this->URLGenerator = \OC::$server->getURLGenerator();

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
			return $this->URLGenerator->linkToRoute($route, $parameters);
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
	 * @return void
	 * @throws WebsiteNotPermittedException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function assertViewerAccess(string $path, array $meta = [])
	{
		$exceptionClass = WebsiteNotPermittedException::class;
		if ($this->getOption('private') !== '1') {
			$groupAccess = isset($meta['access']) ? strtolower($meta['access']) : 'public';
			if ($groupAccess === 'public') {
				return;
			}

			if ($this->getViewer() && $this->groupManager->groupExists($groupAccess)) {
				if ($this->groupManager->isInGroup($this->getViewer(), $groupAccess)) {
					return;
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

	/**
	 * @return void
	 * @throws CheckCharsException
	 * @throws MinCharsException
	 * @throws PathContainSpecificFoldersException
	 */
	public function hasToBeFilledWithValidEntries()
	{
		$this->hasToBeFilledWithNonEmptyValues();
		$this->pathCantContainSpecificFolders();

		if (MiscService::checkChars($this->getSite(), MiscService::ALPHA_NUMERIC_SCORES) === false) {
			throw new CheckCharsException(
				$this->l10n->t('The address of the website can only contains alpha numeric chars')
			);
		}
	}

	/**
	 * @throws MinCharsException
	 */
	private function hasToBeFilledWithNonEmptyValues()
	{
		if (strlen($this->getSite()) < self::SITE_LENGTH_MIN) {
			throw new MinCharsException($this->l10n->t('The address of the website must be longer'));
		}

		if (strlen($this->getName()) < self::NAME_LENGTH_MIN) {
			throw new MinCharsException($this->l10n->t('The name of the website must be longer'));
		}
	}

	/**
	 * this is overkill - NC does not allow to create directory outside of the users' filesystem
	 * Not sure that there is a single use for this security check
	 *
	 * @param string $path
	 *
	 * @throws PathContainSpecificFoldersException
	 */
	public function pathCantContainSpecificFolders($path = '')
	{
		if ($path === '') {
			$path = $this->getPath();
		}

		$limit = [ '.', '..' ];

		$folders = explode('/', $path);
		foreach ($folders as $folder) {
			if (in_array($folder, $limit)) {
				throw new PathContainSpecificFoldersException();
			}
		}
	}

	/**
	 * @param string $userId
	 *
	 * @throws UserIsNotOwnerException
	 */
	public function hasToBeOwnedBy($userId)
	{
		if ($this->getUserId() !== $userId) {
			throw new UserIsNotOwnerException();
		}
	}
}
