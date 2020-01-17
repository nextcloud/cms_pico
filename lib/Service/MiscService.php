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

namespace OCA\CMSPico\Service;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\ComposerException;
use OCA\CMSPico\Exceptions\FilesystemNotWritableException;
use OCA\CMSPico\Files\FileInterface;
use OCP\Files\AlreadyExistsException;
use OCP\Files\GenericFileException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\Security\ISecureRandom;

class MiscService
{
	/** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/** @var string[] */
	private $textFileMagic;

	/** @var string[] */
	private $binaryFileMagic;

	/**
	 * MiscService constructor.
	 *
	 * @param IL10N         $l10n
	 * @param ConfigService $configService
	 * @param FileService   $fileService
	 */
	public function __construct(IL10N $l10n, ConfigService $configService, FileService $fileService)
	{
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->fileService = $fileService;

		$this->textFileMagic = [
			hex2bin('EFBBBF'),
			hex2bin('0000FEFF'),
			hex2bin('FFFE0000'),
			hex2bin('FEFF'),
			hex2bin('FFFE')
		];

		$this->binaryFileMagic = [
			'%PDF',
			hex2bin('89') . 'PNG'
		];
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 * @throws InvalidPathException
	 */
	public function normalizePath(string $path): string
	{
		$path = str_replace('\\', '/', $path);
		$pathParts = explode('/', $path);

		$resultParts = [];
		foreach ($pathParts as $pathPart) {
			if (($pathPart === '') || ($pathPart === '.')) {
				continue;
			} elseif ($pathPart === '..') {
				if (empty($resultParts)) {
					throw new InvalidPathException();
				}

				array_pop($resultParts);
				continue;
			}

			$resultParts[] = $pathPart;
		}

		return implode('/', $resultParts);
	}

	/**
	 * @param string      $path
	 * @param string|null $basePath
	 *
	 * @return string
	 * @throws InvalidPathException
	 */
	public function getRelativePath(string $path, string $basePath = null): string
	{
		if (!$basePath) {
			$basePath = \OC::$SERVERROOT;
		}

		$basePath = $this->normalizePath($basePath);
		$basePathLength = strlen($basePath);

		$path = $this->normalizePath($path);

		if ($path === $basePath) {
			return '';
		} elseif (substr($path, 0, $basePathLength + 1) === $basePath . '/') {
			return substr($path, $basePathLength + 1);
		} else {
			throw new InvalidPathException();
		}
	}

	/**
	 * @param string $path
	 * @param string $fileExtension
	 *
	 * @return false|string
	 * @throws InvalidPathException
	 */
	public function dropFileExtension(string $path, string $fileExtension): string
	{
		$fileName = basename($path);
		$fileExtensionPos = strrpos($fileName, '.');
		if (($fileExtensionPos === false) || (substr($fileName, $fileExtensionPos) !== $fileExtension)) {
			throw new InvalidPathException();
		}

		return substr($path, 0, strlen($path) - strlen($fileExtension));
	}

	/**
	 * @param FileInterface $file
	 *
	 * @return bool
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function isBinaryFile(FileInterface $file): bool
	{
		try {
			$buffer = file_get_contents($file->getLocalPath(), false, null, 0, 1024);
		} catch (\Exception $e) {
			$buffer = false;
		}

		if ($buffer === false) {
			$buffer = substr($file->getContent(), 0, 1024);
		}

		if ($buffer === '') {
			return false;
		}

		foreach ($this->textFileMagic as $textFileMagic) {
			if (substr_compare($buffer, $textFileMagic, 0, strlen($textFileMagic)) === 0) {
				return false;
			}
		}

		foreach ($this->binaryFileMagic as $binaryFileMagic) {
			if (substr_compare($buffer, $binaryFileMagic, 0, strlen($binaryFileMagic)) === 0) {
				return true;
			}
		}

		return (strpos($buffer, "\0") !== false);
	}

	/**
	 * @param int    $length
	 * @param string $prefix
	 * @param string $suffix
	 *
	 * @return string
	 */
	public function getRandom(int $length = 10, string $prefix = '', string $suffix = ''): string
	{
		$randomChars = ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS;
		$random = \OC::$server->getSecureRandom()->generate($length, $randomChars);
		return ($prefix ? $prefix . '.' : '') . $random . ($suffix ? '.' . $suffix : '');
	}

	/**
	 * @throws ComposerException
	 */
	public function checkComposer(): void
	{
		$appPath = Application::getAppPath();
		if (!is_file($appPath . '/vendor/autoload.php')) {
			try {
				$relativeAppPath = $this->getRelativePath($appPath) . '/';
			} catch (InvalidPathException $e) {
				$relativeAppPath = 'apps/' . Application::APP_NAME . '/';
			}

			throw new ComposerException($this->l10n->t(
				'Failed to enable Pico CMS for Nextcloud: Couldn\'t find "%s". Make sure to install the app\'s '
				. 'dependencies by executing `composer install` in the app\'s install directory below "%s". '
				. 'Then try again enabling Pico CMS for Nextcloud.',
				[ $relativeAppPath . 'vendor/autoload.php', $relativeAppPath ]
			));
		}
	}

	/**
	 * @throws FilesystemNotWritableException
	 */
	public function checkPublicFolder(): void
	{
		$publicFolder = $this->fileService->getPublicFolder();

		try {
			try {
				$publicThemesFolder = $publicFolder->newFolder(PicoService::DIR_THEMES);
			} catch (AlreadyExistsException $e) {
				$publicThemesFolder = $publicFolder->getFolder(PicoService::DIR_THEMES);
			}

			$publicThemesTestFileName = $this->getRandom(10, 'tmp', Application::APP_NAME . '-test');
			$publicThemesTestFile = $publicThemesFolder->newFile($publicThemesTestFileName);
			$publicThemesTestFile->delete();

			try {
				$publicPluginsFolder = $publicFolder->newFolder(PicoService::DIR_PLUGINS);
			} catch (AlreadyExistsException $e) {
				$publicPluginsFolder = $publicFolder->getFolder(PicoService::DIR_PLUGINS);
			}

			$publicPluginsTestFileName = $this->getRandom(10, 'tmp', Application::APP_NAME . '-test');
			$publicPluginsTestFile = $publicPluginsFolder->newFile($publicPluginsTestFileName);
			$publicPluginsTestFile->delete();
		} catch (NotPermittedException $e) {
			try {
				$appDataPublicPath = Application::getAppPath() . '/appdata_public';
				$appDataPublicPath = $this->getRelativePath($appDataPublicPath) . '/';
			} catch (InvalidPathException $e) {
				$appDataPublicPath = 'apps/' . Application::APP_NAME . '/appdata_public/';
			}

			try {
				$dataPath = $this->configService->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');
				$dataPath = $this->getRelativePath($dataPath) . '/';
			} catch (InvalidPathException $e) {
				$dataPath = 'data/';
			}

			throw new FilesystemNotWritableException($this->l10n->t(
				'Failed to enable Pico CMS for Nextcloud: The webserver has no permission to create files and '
				. 'folders below "%s". Make sure to give the webserver write access to this directory by '
				. 'changing its permissions and ownership to the same as of your "%s" directory. Then try '
				. 'again enabling Pico CMS for Nextcloud.',
				[ $appDataPublicPath, $dataPath ]
			));
		}
	}
}
