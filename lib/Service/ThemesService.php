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
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Files\FolderInterface;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;

class ThemesService
{
	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/**
	 * ThemesService constructor.
	 *
	 * @param ConfigService $configService
	 * @param FileService   $fileService
	 */
	public function __construct(ConfigService $configService, FileService $fileService)
	{
		$this->configService = $configService;
		$this->fileService = $fileService;
	}

	/**
	 * @param $theme
	 *
	 * @throws ThemeNotFoundException
	 */
	public function assertValidTheme($theme)
	{
		if (!in_array($theme, $this->getSystemThemes()) && !in_array($theme, $this->getCustomThemes())) {
			throw new ThemeNotFoundException();
		}
	}

	/**
	 * @return string[]
	 */
	public function getThemes(): array
	{
		return array_merge($this->getSystemThemes(), $this->getCustomThemes());
	}

	/**
	 * @return string[]
	 */
	public function getSystemThemes(): array
	{
		$systemThemesFolder = $this->fileService->getSystemFolder(PicoService::DIR_THEMES);
		$systemThemesFolder->sync(FolderInterface::SYNC_SHALLOW);

		$systemThemes = [];
		foreach ($systemThemesFolder->listing() as $themeFolder) {
			if ($themeFolder->isFolder()) {
				$systemThemes[] = $themeFolder->getName();
			}
		}

		return $systemThemes;
	}

	/**
	 * @return string[]
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
		$currentThemes = $this->getThemes();

		$customThemesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_THEMES);
		$customThemesFolder->sync(FolderInterface::SYNC_SHALLOW);

		$newCustomThemes = [];
		foreach ($customThemesFolder->listing() as $themeFolder) {
			$theme = $themeFolder->getName();
			if ($themeFolder->isFolder() && !in_array($theme, $currentThemes)) {
				$newCustomThemes[] = $theme;
			}
		}

		return $newCustomThemes;
	}

	/**
	 * @param string $theme
	 */
	public function publishCustomTheme(string $theme)
	{
		$publicThemesFolder = $this->fileService->getPublicFolder(PicoService::DIR_THEMES);

		$appDataThemesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_THEMES);
		$appDataThemesFolder->sync(FolderInterface::SYNC_SHALLOW);

		/** @var FolderInterface $appDataThemeFolder */
		$appDataThemeFolder = $appDataThemesFolder->get($theme);
		$appDataThemeFolder->sync();

		$appDataThemeFolder->copy($publicThemesFolder);
	}

	/**
	 * @param string $theme
	 */
	public function depublishCustomTheme(string $theme)
	{
		$publicThemesFolder = $this->fileService->getPublicFolder(PicoService::DIR_THEMES);

		try {
			$publicThemesFolder->get($theme)->delete();
		} catch (NotFoundException $e) {}
	}

	/**
	 * @return string
	 */
	public function getThemesPath(): string
	{
		$appPath = \OC_App::getAppPath(Application::APP_NAME);
		return $appPath . '/appdata_public/' . PicoService::DIR_THEMES . '/';
	}

	/**
	 * @return string
	 */
	public function getThemesUrl(): string
	{
		return \OC_App::getAppWebPath(Application::APP_NAME) . '/appdata_public/' . PicoService::DIR_THEMES . '/';
	}
}
