<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2020, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
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

namespace OCA\CMSPico\Tests\Utils\Manager;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Files\StorageScanner;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\MiscService;
use OCP\Files\GenericFileException;
use OCP\Files\InvalidPathException;
use OCP\IUserManager;

class TestFilesManager extends TestManager
{
	/** @var IUserManager */
	protected $userManager;

	/** @var StorageScanner */
	protected $scanner;

	/** @var MiscService */
	protected $miscService;

	/** @var string|null */
	protected $pathSuffix;

	/** @var array{0: string, 1: string}[] */
	protected $paths = [];

	/** @var string|null */
	private static $sourcePath;

	/** @var string|null */
	private static $appDataPath;

	/** @var string|null */
	private static $publicAppDataPath;

	public function setUp(): void
	{
		$this->userManager = \OC::$server->getUserManager();
		$this->scanner = \OC::$server->query(StorageScanner::class);
		$this->miscService = \OC::$server->query(MiscService::class);
	}

	public function tearDown(): void
	{
		foreach ($this->paths as $pathId => [ , $targetPathFull ]) {
			$this->deleteRecursive($pathId, $targetPathFull);
		}

		$this->paths = [];
	}

	public function copyAppData(string $sourcePath, string $targetPath = null): string
	{
		[ $pathId, $sourcePath, $targetPath ] = $this->prepareCopyPaths($sourcePath, $targetPath);

		$sourcePathFull = static::getSourcePath($sourcePath);
		if (!file_exists($sourcePathFull)) {
			$errorTemplate = 'Unable to copy test file "%s": Source path "%s" not found';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $sourcePathFull));
		}

		$targetPathFull = static::getAppDataPath($targetPath);
		if (file_exists($targetPathFull)) {
			$errorTemplate = 'Unable to copy test file "%s": Target path "%s" exists';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $targetPathFull));
		}

		$this->setupTargetPath($pathId, static::getAppDataPath(), $targetPath);
		$this->copyRecursive($pathId, $sourcePath, $targetPathFull);

		$this->paths[$pathId] = [ $sourcePathFull, $targetPathFull ];
		return basename($targetPath);
	}

	public function copyUserData(string $userId, string $sourcePath, string $targetPath = null): string
	{
		[ $pathId, $sourcePath, $targetPath ] = $this->prepareCopyPaths($sourcePath, $targetPath);

		$sourcePathFull = static::getSourcePath($sourcePath);
		if (!file_exists($sourcePathFull)) {
			$errorTemplate = 'Unable to copy test file "%s": Source path "%s" not found';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $sourcePathFull));
		}

		if (!$this->userManager->userExists($userId)) {
			$errorTemplate = 'Unable to copy test file "%s": User "%s" not found';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $userId));
		}

		$targetPathBase = static::getUserDataPath($userId);
		$targetPathFull = $targetPathBase . '/' . $targetPath;
		$targetPathMount = basename($targetPathBase) . '/' . $targetPath;
		if (file_exists($targetPathFull)) {
			$errorTemplate = 'Unable to copy test file "%s": Target path "%s" exists';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $targetPathFull));
		}

		$this->setupTargetPath($pathId, dirname($targetPathBase), $targetPathMount);
		$this->copyRecursive($pathId, $sourcePath, $targetPathFull);

		$this->scanner->scan($targetPathMount);

		$this->paths[$pathId] = [ $sourcePathFull, $targetPathFull ];
		return basename($targetPath);
	}

	private function prepareCopyPaths(string $sourcePath, ?string $targetPath): array
	{
		if (!preg_match('#^([^/]+)/([^/]+)$#', $sourcePath)) {
			$errorTemplate = 'Unable to copy test file: Invalid source path "%s"';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $sourcePath));
		}

		if ($targetPath !== null) {
			try {
				$targetPath = $this->miscService->normalizePath($targetPath);

				if ($targetPath === '') {
					throw new InvalidPathException();
				}
			} catch (InvalidPathException $e) {
				$errorTemplate = 'Unable to copy test file: Invalid target path "%s"';
				throw new \InvalidArgumentException(sprintf($errorTemplate, $targetPath));
			}
		} else {
			$targetPath = $sourcePath;
		}

		$pathId = $targetPath;
		$targetPath .= '_' . $this->getTestPathSuffix();

		if (isset($this->paths[$pathId])) {
			$errorTemplate = 'Unable to copy test file "%s": Test path exists';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId));
		}

		return [ $pathId, $sourcePath, $targetPath ];
	}

	private function setupTargetPath(string $pathId, string $basePath, string $path): void
	{
		$pathPartial = '';
		$pathParts = explode('/', $path);
		foreach (array_slice($pathParts, 0, -1) as $pathPart) {
			$pathPart = $pathPartial . $pathPart;
			if (!file_exists($basePath . '/' . $pathPart)) {
				if (!@mkdir($basePath . '/' . $pathPart)) {
					throw new GenericFileException();
				}
			} elseif (!is_dir($basePath . '/' . $pathPart)) {
				$errorTemplate = 'Unable to copy test file "%s": Invalid target path "%s"';
				throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $pathPart));
			}

			$pathPartial = $pathPart . '/';
		}
	}

	private function copyRecursive(string $pathId, string $sourcePath, string $targetPathFull): void
	{
		$sourcePathFull = static::getSourcePath($sourcePath);

		if (is_link($sourcePathFull)) {
			$link = @readlink($sourcePathFull);
			if ($link === false) {
				throw new GenericFileException();
			}

			if ($link[0] === '/') {
				$errorTemplate = 'Unable to copy test file "%s": Encountered invalid symbolic link "%s" targeting "%s"';
				throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $sourcePathFull, $link));
			}

			try {
				$this->miscService->normalizePath(dirname($sourcePath) . '/' . $link);
			} catch (InvalidPathException $e) {
				$errorTemplate = 'Unable to copy test file "%s": Encountered invalid symbolic link "%s" targeting "%s"';
				throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $sourcePathFull, $link));
			}

			// resolve symbolic links
		}

		if (is_file($sourcePathFull)) {
			if (!@copy($sourcePathFull, $targetPathFull)) {
				throw new GenericFileException();
			}
		} elseif (is_dir($sourcePathFull)) {
			$files = @scandir($sourcePathFull);
			if ($files === false) {
				throw new GenericFileException();
			}

			if (!@mkdir($targetPathFull)) {
				throw new GenericFileException();
			}

			foreach ($files as $file) {
				if (($file === '.') || ($file === '..')) {
					continue;
				}

				$this->copyRecursive($pathId, $sourcePath . '/' . $file, $targetPathFull . '/' . $file);
			}
		} else {
			$errorTemplate = 'Unable to copy test file "%s": Encountered special file "%s"';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $sourcePathFull));
		}
	}

	public function delete(string $pathId): void
	{
		if (!isset($this->paths[$pathId])) {
			$errorTemplate = 'Unable to delete test file "%s": Test path not found';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId));
		}

		[ , $fullPath ] = $this->paths[$pathId];

		if (!file_exists($fullPath)) {
			$errorTemplate = 'Unable to delete test file "%s": Path "%s" not found';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $fullPath));
		}

		$this->deleteRecursive($pathId, $fullPath);

		unset($this->paths[$pathId]);
	}

	private function deleteRecursive(string $pathId, string $fullPath): void
	{
		if (is_file($fullPath)) {
			if (!@unlink($fullPath)) {
				throw new GenericFileException();
			}
		} elseif (is_dir($fullPath)) {
			$files = @scandir($fullPath);
			if ($files === false) {
				throw new GenericFileException();
			}

			foreach ($files as $file) {
				if (($file === '.') || ($file === '..')) {
					continue;
				}

				$this->deleteRecursive($pathId, $fullPath . '/' . $file);
			}

			if (!@rmdir($fullPath)) {
				throw new GenericFileException();
			}
		} elseif (file_exists($fullPath)) {
			$errorTemplate = 'Unable to delete test file "%s": Encountered special file "%s"';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId, $fullPath));
		}
	}

	public function addPath(string $pathId, string $fullPath = null): self
	{
		if (isset($this->paths[$pathId])) {
			$errorTemplate = 'Unable to register test file "%s": Test path exists';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId));
		}

		$fullPath = $fullPath ?? static::getAppDataPath($pathId);
		$this->paths[$pathId] = [ null, $fullPath ];

		return $this;
	}

	public function removePath(string $pathId): self
	{
		if (!isset($this->paths[$pathId])) {
			$errorTemplate = 'Unable to unregister test file "%s": Test path not found';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId));
		}

		unset($this->paths[$pathId]);

		return $this;
	}

	public function getPath(string $pathId): array
	{
		if (!isset($this->paths[$pathId])) {
			$errorTemplate = 'Unable to return test file "%s": Test path not found';
			throw new \InvalidArgumentException(sprintf($errorTemplate, $pathId));
		}

		return $this->paths[$pathId];
	}

	protected function getTestPathSuffix(): string
	{
		if ($this->pathSuffix === null) {
			$this->pathSuffix = substr(hash('sha256', $this->testCaseName), 0, 16);
		}

		return $this->pathSuffix;
	}

	public static function getSourcePath(string $path = null): string
	{
		/** @var MiscService $miscService */
		$miscService = \OC::$server->query(MiscService::class);

		if (self::$sourcePath === null) {
			self::$sourcePath = '/' . $miscService->normalizePath(__DIR__ . '/../../data');
		}

		if ($path !== null) {
			$path = $miscService->normalizePath($path);
		}

		return self::$sourcePath . ($path ? '/' . $path : '');
	}

	public static function getTargetPath(string $path): string
	{
		$basePath = strstr($path, '/', true) ?: null;
		switch ($basePath) {
			case 'appdata':
				return static::getAppDataPath(substr($path, strlen($basePath) + 1));

			case 'appdata_public':
				return static::getPublicAppDataPath(substr($path, strlen($basePath) + 1));

			default:
				return static::getUserDataPath($basePath, substr($path, strlen($basePath) + 1));
		}
	}

	public static function getAppDataPath(string $path = null): string
	{
		/** @var MiscService $miscService */
		$miscService = \OC::$server->query(MiscService::class);

		if (self::$appDataPath === null) {
			/** @var FileService $fileService */
			$fileService = \OC::$server->query(FileService::class);
			self::$appDataPath = '/' . $miscService->normalizePath($fileService->getAppDataFolderPath());
		}

		if ($path !== null) {
			$path = $miscService->normalizePath($path);
		}

		return self::$appDataPath . ($path ? '/' . $path : '');
	}

	public static function getPublicAppDataPath(string $path = null): string
	{
		/** @var MiscService $miscService */
		$miscService = \OC::$server->query(MiscService::class);

		if (self::$publicAppDataPath === null) {
			self::$publicAppDataPath = '/' . $miscService->normalizePath(Application::getAppPath() . '/appdata_public');
		}

		if ($path !== null) {
			/** @var ConfigService $configService */
			$configService = \OC::$server->query(ConfigService::class);

			$path = $miscService->normalizePath($path);

			$basePath = strstr($path, '/', true) ?: null;
			switch ($basePath) {
				case 'themes':
					$themesETag = $configService->getAppValue(ConfigService::THEMES_ETAG);
					$path = $basePath
						. ($themesETag ? '/' . $themesETag : '')
						. '/' . substr($path, strlen($basePath) + 1);
					break;

				case 'plugins':
					$pluginsETag = $configService->getAppValue(ConfigService::PLUGINS_ETAG);
					$path = $basePath
						. ($pluginsETag ? '/' . $pluginsETag : '')
						. '/' . substr($path, strlen($basePath) + 1);
					break;
			}
		}

		return self::$publicAppDataPath . ($path ? '/' . $path : '');
	}

	public static function getUserDataPath(string $userId, string $path = null): string
	{
		$userManager = \OC::$server->getUserManager();

		if (!$userManager->userExists($userId)) {
			throw new InvalidPathException();
		}

		$user = $userManager->get($userId);
		$homePath = $user->getHome();

		if ($path !== null) {
			/** @var MiscService $miscService */
			$miscService = \OC::$server->query(MiscService::class);

			$path = $miscService->normalizePath($path);
		}

		return $homePath . ($path ? '/' . $path : '');
	}
}
