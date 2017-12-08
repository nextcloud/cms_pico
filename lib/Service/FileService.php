<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\CMSPico\Service;

use DirectoryIterator;
use Exception;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Model\TemplateFile;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;

class FileService {


	const INSTALL_DIR = __DIR__ . '/../../Pico/';

	/** @var IRootFolder */
	private $rootFolder;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var Folder */
	private $appDataFolder;


	/**
	 * ConfigService constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IRootFolder $rootFolder, ConfigService $configService, MiscService $miscService
	) {
		$this->rootFolder = $rootFolder;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


//	public function getAppDataFolderContent($dir) {
	public function getDirectoriesFromAppDataFolder($dir) {
// do we still use DirectoryIterator as files are in DB ?
		$all = [];
		foreach (new DirectoryIterator($this->getAppDataFolderAbsolutePath($dir)) as $file) {
			if (!$file->isDir() || substr($file->getFilename(), 0, 1) === '.') {
				continue;
			}

			$all[] = $file->getFilename();
		}

		return $all;
	}


	/**
	 * @param string $base
	 * @param string $dir
	 *
	 * @return string[]
	 */
	public function getSourceFiles($base, $dir = '') {

		$base = MiscService::endSlash($base);
		$dir = MiscService::endSlash($dir);

		$files = [];
		foreach (new DirectoryIterator($base . $dir) as $file) {

			if (substr($file->getFilename(), 0, 1) === '.') {
				continue;
			}

			if ($file->isDir()) {
				$files[] = $dir . $file->getFilename() . '/';
				$files = array_merge($files, $this->getSourceFiles($base, $dir . $file->getFilename()));
				continue;
			}

			$files[] = $dir . $file->getFilename();
		}

		return $files;
	}




/**
 * // TODO: this function should use File from nc, not read on the filesystem
	 * @param string $base
	 * @param string $dir
	 *
	 * @return string[]
	 */
	public function getAppDataFiles($base, $dir = '') {

		$base = MiscService::endSlash($base);
		$dir = MiscService::endSlash($dir);

		$files = [];
		foreach (new DirectoryIterator($base . $dir) as $file) {

			if (substr($file->getFilename(), 0, 1) === '.') {
				continue;
			}

			if ($file->isDir()) {
				$files[] = $dir . $file->getFilename() . '/';
				$files = array_merge($files, $this->getSourceFiles($base, $dir . $file->getFilename()));
				continue;
			}

			$files[] = $dir . $file->getFilename();
		}

		return $files;
	}


	/**
	 * @param string $dir
	 *
	 * @return string
	 */
	public function getAppDataFolderAbsolutePath($dir) {

		$appNode = $this->getAppDataFolder();

		try {
			$appNode->get($dir);
		} catch (NotFoundException $e) {
			$this->createAppDataFolder($appNode, $dir);
		}

		$appPath = MiscService::endSlash($this->configService->getSystemValue('datadirectory', null));
		$appPath .= MiscService::endSlash($appNode->getInternalPath());

		$appPath .= MiscService::endSlash($dir);

		return $appPath;
	}


	/**
	 * Create Appdata Subfolder and duplicate the content from apps/cms_pico/Pico/
	 *
	 * @param Folder $appNode
	 * @param string $dir
	 */
	private function createAppDataFolder(Folder $appNode, $dir) {
		$appFolder = $appNode->newFolder($dir);

		$files = $this->getSourceFiles(self::INSTALL_DIR . $dir);
		foreach ($files as $file) {
			if (substr($file, -1) === '/') {
				$appFolder->newFolder($file);
			} else {
				$newFile = $appFolder->newFile($file);
				$newFile->putContent(file_get_contents(self::INSTALL_DIR . $dir . '/' . $file));
			}
		}
	}


	/**
	 * Get AppData Folder for this app, create it otherwise.
	 *
	 * @return Folder
	 */
	private function getAppDataFolder() {
		if ($this->appDataFolder === null) {

			$instanceId = $this->configService->getSystemValue('instanceid', null);
			$name = 'appdata_' . $instanceId;

			/** @var Folder $globalAppDataFolder */
			try {
				$globalAppDataFolder = $this->rootFolder->get($name);
			} catch (NotFoundException $e) {
				$globalAppDataFolder = $this->rootFolder->newFolder($name);
			}

			try {
				$this->appDataFolder = $globalAppDataFolder->get(Application::APP_NAME);
			} catch (NotFoundException $e) {
				$this->appDataFolder = $globalAppDataFolder->newFolder(Application::APP_NAME);
			}
		}

		return $this->appDataFolder;
	}


}
