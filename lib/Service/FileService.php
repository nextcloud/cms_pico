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

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\LocalFolder;
use OCA\CMSPico\Files\StorageFolder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class FileService
{
	/** @var string */
	const APPDATA_PUBLIC = 'appdata_public';

	/** @var string */
	const APPDATA_SYSTEM = 'appdata';

	/** @var IRootFolder */
	private $rootFolder;

	/** @var ConfigService */
	private $configService;

	/** @var FolderInterface */
	private $publicFolder;

	/** @var FolderInterface */
	private $systemFolder;

	/** @var FolderInterface */
	private $appDataFolder;

	/**
	 * FileService constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param ConfigService $configService
	 */
	public function __construct(IRootFolder $rootFolder, ConfigService $configService)
	{
		$this->rootFolder = $rootFolder;
		$this->configService = $configService;
	}

	/**
	 * @param string|null $folderName
	 *
	 * @return FolderInterface
	 * @throws InvalidPathException
	 * @throws NotPermittedException
	 */
	public function getPublicFolder(string $folderName = null): FolderInterface
	{
		if ($this->publicFolder === null) {
			$publicFolderBasePath = Application::getAppPath() . '/' . self::APPDATA_PUBLIC;
			$this->publicFolder = new LocalFolder('/', $publicFolderBasePath);
		}

		if ($folderName) {
			try {
				return $this->publicFolder->getFolder($folderName);
			} catch (NotFoundException $e) {
				return $this->publicFolder->newFolder($folderName);
			}
		}

		return $this->publicFolder;
	}

	/**
	 * @param string|null $folderName
	 *
	 * @return FolderInterface
	 * @throws InvalidPathException
	 * @throws NotPermittedException
	 */
	public function getSystemFolder(string $folderName = null): FolderInterface
	{
		if ($this->systemFolder === null) {
			$systemFolderBasePath = Application::getAppPath() . '/' . self::APPDATA_SYSTEM;
			$this->systemFolder = new LocalFolder('/', $systemFolderBasePath);
		}

		if ($folderName) {
			try {
				return $this->systemFolder->getFolder($folderName);
			} catch (NotFoundException $e) {
				return $this->systemFolder->newFolder($folderName);
			}
		}

		return $this->systemFolder;
	}

	/**
	 * @param string|null $folderName
	 *
	 * @return FolderInterface
	 * @throws InvalidPathException
	 * @throws NotPermittedException
	 */
	public function getAppDataFolder(string $folderName = null): FolderInterface
	{
		if ($this->appDataFolder === null) {
			$baseFolderName = 'appdata_' . $this->configService->getSystemValue('instanceid');
			$appDataFolderName = Application::APP_NAME;

			$rootFolder = new StorageFolder($this->rootFolder);

			try {
				$baseFolder = $rootFolder->getFolder($baseFolderName);
			} catch (NotFoundException $e) {
				$baseFolder = $rootFolder->newFolder($baseFolderName);
			}

			try {
				$appDataFolder = $baseFolder->getFolder($appDataFolderName);
			} catch (NotFoundException $e) {
				$appDataFolder = $baseFolder->newFolder($appDataFolderName);
			}

			$this->appDataFolder = $appDataFolder->fakeRoot();
		}

		if ($folderName) {
			try {
				return $this->appDataFolder->getFolder($folderName);
			} catch (NotFoundException $e) {
				return $this->appDataFolder->newFolder($folderName);
			}
		}

		return $this->appDataFolder;
	}

	/**
	 * @param string|null $folderName
	 *
	 * @return string
	 */
	public function getAppDataFolderPath(string $folderName = null): string
	{
		$dataFolderPath = $this->configService->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');
		$baseAppDataFolderName = 'appdata_' . $this->configService->getSystemValue('instanceid');
		$appDataFolderPath = Application::APP_NAME . ($folderName ? '/' . $folderName : '');

		return rtrim($dataFolderPath, '/') . '/' . $baseAppDataFolderName . '/' . $appDataFolderPath . '/';
	}

	/**
	 * @return void
	 */
	public function syncAppDataFolder()
	{
		$baseFolderName = 'appdata_' . $this->configService->getSystemValue('instanceid');
		$appDataFolderName = Application::APP_NAME;

		$rootFolder = new StorageFolder($this->rootFolder);
		$rootFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$baseFolder = $rootFolder->getFolder($baseFolderName);
			$baseFolder->sync(FolderInterface::SYNC_SHALLOW);
		} catch (NotFoundException $e) {
			$baseFolder = $rootFolder->newFolder($baseFolderName);
		}

		try {
			$appDataFolder = $baseFolder->getFolder($appDataFolderName);
			$appDataFolder->sync(FolderInterface::SYNC_RECURSIVE);
		} catch (NotFoundException $e) {
			$appDataFolder = $baseFolder->newFolder($appDataFolderName);
		}

		$this->appDataFolder = $appDataFolder->fakeRoot();
	}
}
