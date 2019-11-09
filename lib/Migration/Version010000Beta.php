<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
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

namespace OCA\CMSPico\Migration;

use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\PicoService;
use OCA\CMSPico\Service\ThemesService;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010000Beta extends SimpleMigrationStep
{
	use MigrationTrait;

	/** @var ConfigService */
	private $configService;

	/** @var ThemesService */
	private $themesService;

	/** @var FileService */
	private $fileService;

	/**
	 * Version010000 constructor.
	 */
	public function __construct()
	{
		$this->setLogger(\OC::$server->getLogger());

		$this->configService = \OC::$server->query(ConfigService::class);
		$this->themesService = \OC::$server->query(ThemesService::class);
		$this->fileService = \OC::$server->query(FileService::class);
	}

	/**
	 * @param IOutput  $output
	 * @param \Closure $schemaClosure
	 * @param array    $options
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options)
	{
		$previousAppVersion = $this->configService->getAppValue('installed_version');
		if ($previousAppVersion !== '1.0.0') {
			// v1.0.0-beta.1 wrongly identifies itself as v1.0.0
			// nothing to do if not upgrading from said fake v1.0.0
			return;
		}

		$this->setOutput($output);

		// reset ETags
		$this->configService->deleteAppValue(ConfigService::THEMES_ETAG);
		$this->configService->deleteAppValue(ConfigService::THEMES_ETAG);

		// migrate themes
		$this->migrateDefaultTheme();
		$this->migrateCustomThemes();
	}

	/**
	 * @return void
	 */
	private function migrateDefaultTheme()
	{
		$customThemesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_THEMES);
		$customThemesFolder->sync(FolderInterface::SYNC_SHALLOW);

		$themeName = 'default';
		if ($customThemesFolder->exists($themeName)) {
			$themeFolder = $customThemesFolder->getFolder($themeName);

			$newThemeName = $themeName . '-v0.9';
			for ($i = 1; isset($customThemes[$themeName]); $i++) {
				$newThemeName = $themeName . '-v0.9-dup' . $i;
			}

			$themeFolder->rename($newThemeName);
			$this->themesService->publishCustomTheme($newThemeName);

			$this->logWarning('Renaming old Pico CMS system theme "%s" to "%s"', $themeName, $newThemeName);
		}
	}

	/**
	 * @return void
	 */
	private function migrateCustomThemes()
	{
		$this->logInfo('Downgrading data structure of Pico CMS themes');

		$customThemesJson = $this->configService->getAppValue(ConfigService::CUSTOM_THEMES);
		$customThemes = $customThemesJson ? json_decode($customThemesJson, true) : [];

		$newCustomThemes = [];
		foreach ($customThemes as $themeData) {
			$newCustomThemes[] = $themeData['name'];
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($newCustomThemes));
	}
}
