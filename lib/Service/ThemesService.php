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

use OC\App\AppManager;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Files\FolderInterface;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;

class ThemesService
{
	/** @var AppManager */
	private $appManager;

	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/**
	 * ThemesService constructor.
	 *
	 * @param AppManager    $appManager
	 * @param ConfigService $configService
	 * @param FileService   $fileService
	 */
	function __construct(AppManager $appManager, ConfigService $configService, FileService $fileService)
	{
		$this->appManager = $appManager;
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
		/** @var FolderInterface $systemThemesFolder */
		$systemThemesFolder = $this->fileService->getSystemFolder()->get(PicoService::DIR_THEMES);
		if (!$systemThemesFolder->isFolder()) {
			throw new InvalidPathException();
		}

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

		/** @var FolderInterface $customThemesFolder */
		$customThemesFolder = $this->fileService->getAppDataFolder()->get(PicoService::DIR_THEMES);
		if (!$customThemesFolder->isFolder()) {
			throw new InvalidPathException();
		}


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
		$publicFolder = $this->fileService->getPublicFolder();
		$publicThemesFolder = $publicFolder->get(PicoService::DIR_THEMES);

		$appDataFolder = $this->fileService->getAppDataFolder();
		$appDataFolder->get(PicoService::DIR_THEMES . '/' . $theme)->copy($publicThemesFolder);
	}

	/**
	 * @param string $theme
	 */
	public function depublishCustomTheme(string $theme)
	{
		$publicFolder = $this->fileService->getPublicFolder();

		try {
			$publicFolder->get(PicoService::DIR_THEMES . '/' . $theme)->delete();
		} catch (NotFoundException $e) {}
	}

	/**
	 * @return string
	 */
	public function getThemesPath(): string
	{
		$appPath = $this->appManager->getAppPath(Application::APP_NAME);
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
