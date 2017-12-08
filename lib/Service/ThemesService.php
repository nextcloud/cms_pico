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
use OCA\CMSPico\Exceptions\TemplateDoesNotExistException;
use OCA\CMSPico\Exceptions\ThemeDoesNotExistException;
use OCA\CMSPico\Exceptions\WriteAccessException;
use OCA\CMSPico\Model\TemplateFile;
use OCA\CMSPico\Model\Website;
use OCP\Files\Folder;
use OCP\IL10N;

class ThemesService {

	const THEMES = ['default'];
	const THEMES_DIR = __DIR__ . '/../../Pico/themes/';

	/** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/** @var MiscService */
	private $miscService;

	/**
	 * ThemesService constructor.
	 *
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param FileService $fileService
	 * @param MiscService $miscService
	 */
	function __construct(
		IL10N $l10n, ConfigService $configService, FileService $fileService, MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->fileService = $fileService;
		$this->miscService = $miscService;
	}


	/**
	 * getThemesList();
	 *
	 * returns all available themes.
	 *
	 * @param bool $customOnly
	 *
	 * @return array
	 */
	public function getThemesList($customOnly = false) {
		$themes = [];
		if ($customOnly !== true) {
			$themes = self::THEMES;
		}

		$customs = json_decode($this->configService->getAppValue(ConfigService::CUSTOM_THEMES), true);
		if ($customs !== null) {
			$themes = array_merge($themes, $customs);
		}

		return $themes;
	}


	/**
	 * Check if a theme exist.
	 *
	 * @param $theme
	 *
	 * @throws ThemeDoesNotExistException
	 */
	public function hasToBeAValidTheme($theme) {
		$themes = $this->getThemesList();
		if (!in_array($theme, $themes)) {
			throw new ThemeDoesNotExistException($this->l10n->t('Theme does not exist'));
		}
	}


	/**
	 * returns theme from the Pico/themes/ dir that are not available yet to users.
	 *
	 * @return array
	 */
	public function getNewThemesList() {

		$newThemes = [];
		$currThemes = $this->getThemesList();
		$allThemes = $this->fileService->getDirectoriesFromAppDataFolder(PicoService::DIR_THEMES);

		foreach ($allThemes as $theme) {
			if (!in_array($theme, $currThemes)) {
				$newThemes[] = $theme;
			}
		}

		return $newThemes;
	}

}