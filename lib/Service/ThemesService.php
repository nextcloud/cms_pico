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
use OCA\CMSPico\Exceptions\ThemeAlreadyExistsException;
use OCA\CMSPico\Exceptions\ThemeNotCompatibleException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\LocalFolder;
use OCA\CMSPico\Model\Theme;
use OCP\Files\AlreadyExistsException;
use OCP\Files\NotFoundException;

class ThemesService
{
	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/** @var MiscService */
	private $miscService;

	/** @var bool */
	private $renewedETag = false;

	/**
	 * ThemesService constructor.
	 *
	 * @param ConfigService $configService
	 * @param FileService   $fileService
	 * @param MiscService   $miscService
	 */
	public function __construct(ConfigService $configService, FileService $fileService, MiscService $miscService)
	{
		$this->configService = $configService;
		$this->fileService = $fileService;
		$this->miscService = $miscService;
	}

	/**
	 * @param string $themeName
	 *
	 * @throws ThemeNotFoundException
	 * @throws ThemeNotCompatibleException
	 */
	public function assertValidTheme(string $themeName): void
	{
		$themes = $this->getThemes();

		if (!isset($themes[$themeName])) {
			throw new ThemeNotFoundException();
		}

		if (!$themes[$themeName]['compat']) {
			throw new ThemeNotCompatibleException(
				$themeName,
				$themes[$themeName]['compatReason'],
				$themes[$themeName]['compatReasonData']
			);
		}
	}

	/**
	 * @return array[]
	 */
	public function getThemes(): array
	{
		return $this->getSystemThemes() + $this->getCustomThemes();
	}

	/**
	 * @return array[]
	 */
	public function getSystemThemes(): array
	{
		$json = $this->configService->getAppValue(ConfigService::SYSTEM_THEMES);
		return $json ? json_decode($json, true) : [];
	}

	/**
	 * @return array[]
	 */
	public function getCustomThemes(): array
	{
		$json = $this->configService->getAppValue(ConfigService::CUSTOM_THEMES);
		return $json ? json_decode($json, true) : [];
	}

	/**
	 * @return string[]
	 */
	public function getNewCustomThemes(): array
	{
		$customThemesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_THEMES);
		$customThemesFolder->sync(FolderInterface::SYNC_SHALLOW);

		$currentThemes = $this->getThemes();

		$newCustomThemes = [];
		foreach ($customThemesFolder as $themeFolder) {
			$themeName = $themeFolder->getName();
			if ($themeFolder->isFolder() && !isset($currentThemes[$themeName])) {
				$newCustomThemes[] = $themeName;
			}
		}

		return $newCustomThemes;
	}

	/**
	 * @param string $themeName
	 *
	 * @return Theme
	 * @throws ThemeNotFoundException
	 * @throws ThemeAlreadyExistsException
	 */
	public function publishSystemTheme(string $themeName): Theme
	{
		if (!$themeName) {
			throw new ThemeNotFoundException();
		}

		$systemThemesFolder = $this->fileService->getSystemFolder(PicoService::DIR_THEMES);
		$systemThemesFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$systemThemeFolder = $systemThemesFolder->getFolder($themeName);
		} catch (NotFoundException $e) {
			throw new ThemeNotFoundException();
		}

		$themes = $this->getSystemThemes();
		$themes[$themeName] = $this->publishTheme($systemThemeFolder, Theme::TYPE_SYSTEM);
		$this->configService->setAppValue(ConfigService::SYSTEM_THEMES, json_encode($themes));

		return $themes[$themeName];
	}

	/**
	 * @param string $themeName
	 *
	 * @return Theme
	 * @throws ThemeNotFoundException
	 * @throws ThemeAlreadyExistsException
	 */
	public function publishCustomTheme(string $themeName): Theme
	{
		if (!$themeName) {
			throw new ThemeNotFoundException();
		}

		$systemThemes = $this->getSystemThemes();
		if (isset($systemThemes[$themeName])) {
			throw new ThemeAlreadyExistsException();
		}

		$appDataThemesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_THEMES);
		$appDataThemesFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$appDataThemeFolder = $appDataThemesFolder->getFolder($themeName);
		} catch (NotFoundException $e) {
			throw new ThemeNotFoundException();
		}

		$themes = $this->getCustomThemes();
		$themes[$themeName] = $this->publishTheme($appDataThemeFolder, Theme::TYPE_CUSTOM);
		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($themes));

		return $themes[$themeName];
	}

	/**
	 * @param FolderInterface $themeSourceFolder
	 * @param int             $themeType
	 *
	 * @return Theme
	 * @throws ThemeAlreadyExistsException
	 */
	private function publishTheme(FolderInterface $themeSourceFolder, int $themeType): Theme
	{
		$publicThemesFolder = $this->getThemesFolder(true);

		$themeName = $themeSourceFolder->getName();
		$themeSourceFolder->sync();

		try {
			$publicThemesFolder->getFolder($themeName);
			throw new ThemeAlreadyExistsException();
		} catch (NotFoundException $e) {
			// in fact we want the theme not to exist yet
		}

		/** @var LocalFolder $themeFolder */
		$themeFolder = $themeSourceFolder->copy($publicThemesFolder);
		return new Theme($themeFolder, $themeType);
	}

	/**
	 * @param string $themeName
	 *
	 * @throws ThemeNotFoundException
	 */
	public function depublishCustomTheme(string $themeName): void
	{
		if (!$themeName) {
			throw new ThemeNotFoundException();
		}

		$publicThemesFolder = $this->getThemesFolder();

		try {
			$publicThemesFolder->getFolder($themeName)->delete();
		} catch (NotFoundException $e) {
			throw new ThemeNotFoundException();
		}

		$customThemes = $this->getCustomThemes();
		unset($customThemes[$themeName]);
		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($customThemes));
	}

	/**
	 * @param string $baseThemeName
	 * @param string $themeName
	 *
	 * @return Theme
	 * @throws ThemeNotFoundException
	 * @throws ThemeAlreadyExistsException
	 */
	public function copyTheme(string $baseThemeName, string $themeName): Theme
	{
		if (!$baseThemeName || !$themeName) {
			throw new ThemeNotFoundException();
		}

		$systemThemes = $this->getSystemThemes();
		$customThemes = $this->getCustomThemes();

		if (isset($systemThemes[$themeName]) || isset($customThemes[$themeName])) {
			throw new ThemeAlreadyExistsException();
		}

		try {
			$baseThemeFolder = $this->getThemesFolder()->getFolder($baseThemeName);
		} catch (NotFoundException $e) {
			throw new ThemeNotFoundException();
		}

		try {
			$appDataThemesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_THEMES);
			$baseThemeFolder->copy($appDataThemesFolder, $themeName);
		} catch (AlreadyExistsException $e) {
			throw new ThemeAlreadyExistsException();
		}

		return $this->publishCustomTheme($themeName);
	}

	/**
	 * @param bool $renewETag
	 * @param bool $forceRenewETag
	 *
	 * @return LocalFolder
	 */
	public function getThemesFolder(bool $renewETag = false, bool $forceRenewETag = false): LocalFolder
	{
		$themesBaseFolder = $this->fileService->getPublicFolder(PicoService::DIR_THEMES);

		/** @var LocalFolder $themesFolder */
		$themesFolder = null;

		$themesETag = $this->configService->getAppValue(ConfigService::THEMES_ETAG);
		if ($themesETag) {
			$themesFolder = $themesBaseFolder->getFolder($themesETag);
		}

		if (($renewETag && !$this->renewedETag) || $forceRenewETag || !$themesFolder) {
			$themesETag = $this->miscService->getRandom();

			if ($themesFolder) {
				$themesFolder = $themesFolder->rename($themesETag);
			} else {
				$themesFolder = $themesBaseFolder->newFolder($themesETag);
			}

			$this->configService->setAppValue(ConfigService::THEMES_ETAG, $themesETag);
			$this->renewedETag = true;
		}

		return $themesFolder->fakeRoot();
	}

	/**
	 * @return string
	 */
	public function getThemesPath(): string
	{
		$appPath = Application::getAppPath() . '/';
		$themesPath = 'appdata_public/' . PicoService::DIR_THEMES . '/';
		$themesETag = $this->configService->getAppValue(ConfigService::THEMES_ETAG);
		return $appPath . $themesPath . ($themesETag ? $themesETag . '/' : '');
	}

	/**
	 * @return string
	 */
	public function getThemesUrl(): string
	{
		$appWebPath = Application::getAppWebPath() . '/';
		$themesPath = 'appdata_public/' . PicoService::DIR_THEMES . '/';
		$themesETag = $this->configService->getAppValue(ConfigService::THEMES_ETAG);
		return $appWebPath . $themesPath . ($themesETag ? $themesETag . '/' : '');
	}
}
