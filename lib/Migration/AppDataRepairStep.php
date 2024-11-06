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

use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\PicoService;
use OCA\CMSPico\Service\PluginsService;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\Files\NotFoundException;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class AppDataRepairStep implements IRepairStep
{
	use MigrationTrait;

	/** @var IGroupManager */
	private $groupManager;

	/** @var WebsitesService */
	private $websitesService;

	/** @var ConfigService */
	private $configService;

	/** @var TemplatesService */
	private $templatesService;

	/** @var ThemesService */
	private $themesService;

	/** @var PluginsService */
	private $pluginsService;

	/** @var FileService */
	private $fileService;

	/** @var MiscService */
	private $miscService;

	/** @var bool */
	private $locked = false;

	/**
	 * AppDataRepairStep constructor.
	 *
	 * @param LoggerInterface  $logger
	 * @param IGroupManager    $groupManager
	 * @param WebsitesService  $websitesService
	 * @param ConfigService    $configService
	 * @param TemplatesService $templatesService
	 * @param ThemesService    $themesService
	 * @param PluginsService   $pluginsService
	 * @param FileService      $fileService
	 * @param MiscService      $miscService
	 */
	public function __construct(
		LoggerInterface $logger,
		IGroupManager $groupManager,
		WebsitesService $websitesService,
		ConfigService $configService,
		TemplatesService $templatesService,
		ThemesService $themesService,
		PluginsService $pluginsService,
		FileService $fileService,
		MiscService $miscService
	) {
		$this->setLogger($logger);

		$this->groupManager = $groupManager;
		$this->websitesService = $websitesService;
		$this->configService = $configService;
		$this->templatesService = $templatesService;
		$this->themesService = $themesService;
		$this->pluginsService = $pluginsService;
		$this->fileService = $fileService;
		$this->miscService = $miscService;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return 'Preparing app data of Pico CMS for Nextcloud';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output): void
	{
		$this->setOutput($output);

		// never run AppDataRepairStep multiple times for the same session
		// this might happen if you update and enable the app at the same time
		if ($this->locked) {
			$this->logInfo('Pico CMS\' app data has been prepared already, skipping …');
			return;
		}

		$this->locked = true;

		$this->logInfo('Checking Pico CMS requirements …');
		$this->miscService->checkComposer();
		$this->miscService->checkPublicFolder();

		$this->logInfo('Rebuilding Pico CMS app settings …');
		$this->rebuildAppSettings();

		$this->logInfo('Syncing Pico CMS app data folder …');
		$this->syncAppDataFolder();

		$this->logInfo('Copying Pico CMS config …');
		$this->copyConfig();

		$this->logInfo('Registering Pico CMS templates …');
		$this->registerTemplates();

		$this->logInfo('Publishing Pico CMS themes …');
		$this->publishThemes();

		$this->logInfo('Publishing Pico CMS plugins …');
		$this->publishPlugins();
	}

	/**
	 * @return void
	 */
	private function rebuildAppSettings(): void
	{
		$limitGroups = $this->websitesService->getLimitGroups();
		$limitGroups = array_values(array_filter($limitGroups, [ $this->groupManager, 'groupExists' ]));
		$this->websitesService->setLimitGroups($limitGroups);
	}

	/**
	 * @return void
	 */
	private function syncAppDataFolder(): void
	{
		$this->fileService->syncAppDataFolder();
	}

	/**
	 * @return void
	 */
	private function copyConfig(): void
	{
		$appDataConfigFolder = $this->fileService->getAppDataFolder(PicoService::DIR_CONFIG);
		$systemConfigFolder = $this->fileService->getSystemFolder(PicoService::DIR_CONFIG);

		foreach ($systemConfigFolder as $configFile) {
			$configFileName = $configFile->getName();

			if (!$configFile->isFile()) {
				continue;
			}

			try {
				$appDataConfigFolder->getFile($configFileName)->delete();
				$this->logWarning('Replacing Pico CMS config file "%s"', $configFileName);
			} catch (NotFoundException $e) {
				$this->logInfo('Adding Pico CMS config file "%s"', $configFileName);
			}

			$configFile->copy($appDataConfigFolder);
		}
	}

	/**
	 * @return void
	 */
	private function registerTemplates(): void
	{
		$this->registerSystemTemplates();
		$this->registerCustomTemplates();
	}

	/**
	 * @return void
	 */
	private function registerSystemTemplates(): void
	{
		$systemTemplatesFolder = $this->fileService->getSystemFolder(PicoService::DIR_TEMPLATES);

		$oldSystemTemplates = $this->templatesService->getSystemTemplates();
		$this->configService->deleteAppValue(ConfigService::SYSTEM_TEMPLATES);

		foreach ($systemTemplatesFolder as $templateFolder) {
			$templateName = $templateFolder->getName();
			if ($templateFolder->isFolder()) {
				$this->templatesService->registerSystemTemplate($templateName);
			}
		}

		$oldSystemTemplates = array_keys($oldSystemTemplates);
		$newSystemTemplates = array_keys($this->templatesService->getSystemTemplates());
		$this->logChanges('Pico CMS system template', $newSystemTemplates, $oldSystemTemplates, true);
	}

	/**
	 * @return void
	 */
	private function registerCustomTemplates(): void
	{
		$appDataTemplatesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_TEMPLATES);

		$oldCustomTemplates = $this->templatesService->getCustomTemplates();
		$this->configService->deleteAppValue(ConfigService::CUSTOM_TEMPLATES);

		$systemTemplates = $this->templatesService->getSystemTemplates();
		foreach ($appDataTemplatesFolder as $templateFolder) {
			$templateName = $templateFolder->getName();
			if ($templateFolder->isFolder()) {
				if (isset($oldCustomTemplates[$templateName]) && !isset($systemTemplates[$templateName])) {
					$this->templatesService->registerCustomTemplate($templateName);
				}
			}
		}

		$oldCustomTemplates = array_keys($oldCustomTemplates);
		$newCustomTemplates = array_keys($this->templatesService->getCustomTemplates());
		$this->logChanges('Pico CMS custom template', $newCustomTemplates, $oldCustomTemplates);
	}

	/**
	 * @return void
	 */
	private function publishThemes(): void
	{
		$publicThemesFolder = $this->fileService->getPublicFolder(PicoService::DIR_THEMES);
		$publicThemesFolder->truncate();

		$this->configService->deleteAppValue(ConfigService::THEMES_ETAG);

		$this->publishSystemThemes();
		$this->publishCustomThemes();
	}

	/**
	 * @return void
	 */
	private function publishSystemThemes(): void
	{
		$systemThemesFolder = $this->fileService->getSystemFolder(PicoService::DIR_THEMES);

		$oldSystemThemes = $this->themesService->getSystemThemes();
		$this->configService->deleteAppValue(ConfigService::SYSTEM_THEMES);

		foreach ($systemThemesFolder as $themeFolder) {
			$themeName = $themeFolder->getName();
			if ($themeFolder->isFolder()) {
				$this->themesService->publishSystemTheme($themeName);
			}
		}

		$oldSystemThemes = array_keys($oldSystemThemes);
		$newSystemThemes = array_keys($this->themesService->getSystemThemes());
		$this->logChanges('Pico CMS system theme', $newSystemThemes, $oldSystemThemes, true);
	}

	/**
	 * @return void
	 */
	private function publishCustomThemes(): void
	{
		$appDataThemesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_THEMES);

		$oldCustomThemes = $this->themesService->getCustomThemes();
		$this->configService->deleteAppValue(ConfigService::CUSTOM_THEMES);

		$systemThemes = $this->themesService->getSystemThemes();
		foreach ($appDataThemesFolder as $themeFolder) {
			$themeName = $themeFolder->getName();
			if ($themeFolder->isFolder()) {
				if (isset($oldCustomThemes[$themeName]) && !isset($systemThemes[$themeName])) {
					$this->themesService->publishCustomTheme($themeName);
				}
			}
		}

		$oldCustomThemes = array_keys($oldCustomThemes);
		$newCustomThemes = array_keys($this->themesService->getCustomThemes());
		$this->logChanges('Pico CMS custom theme', $newCustomThemes, $oldCustomThemes);
	}

	/**
	 * @return void
	 */
	private function publishPlugins(): void
	{
		$publicPluginsFolder = $this->fileService->getPublicFolder(PicoService::DIR_PLUGINS);
		$publicPluginsFolder->truncate();

		$this->configService->deleteAppValue(ConfigService::PLUGINS_ETAG);

		$this->publishSystemPlugins();
		$this->publishCustomPlugins();
	}

	/**
	 * @return void
	 */
	private function publishSystemPlugins(): void
	{
		$systemPluginsFolder = $this->fileService->getSystemFolder(PicoService::DIR_PLUGINS);

		$oldSystemPlugins = $this->pluginsService->getSystemPlugins();
		$this->configService->deleteAppValue(ConfigService::SYSTEM_PLUGINS);

		foreach ($systemPluginsFolder as $pluginFolder) {
			$pluginName = $pluginFolder->getName();
			if ($pluginFolder->isFolder()) {
				$this->pluginsService->publishSystemPlugin($pluginName);
			}
		}

		$oldSystemPlugins = array_keys($oldSystemPlugins);
		$newSystemPlugins = array_keys($this->pluginsService->getSystemPlugins());
		$this->logChanges('Pico CMS system plugin', $newSystemPlugins, $oldSystemPlugins, true);
	}

	/**
	 * @return void
	 */
	private function publishCustomPlugins(): void
	{
		$appDataPluginsFolder = $this->fileService->getAppDataFolder(PicoService::DIR_PLUGINS);

		$oldCustomPlugins = $this->pluginsService->getCustomPlugins();
		$this->configService->deleteAppValue(ConfigService::CUSTOM_PLUGINS);

		$systemPlugins = $this->pluginsService->getSystemPlugins();
		foreach ($appDataPluginsFolder as $pluginFolder) {
			$pluginName = $pluginFolder->getName();
			if ($pluginFolder->isFolder()) {
				if (isset($oldCustomPlugins[$pluginName]) && !isset($systemPlugins[$pluginName])) {
					$this->pluginsService->publishCustomPlugin($pluginName);
				}
			}
		}

		$oldCustomPlugins = array_keys($oldCustomPlugins);
		$newCustomPlugins = array_keys($this->pluginsService->getCustomPlugins());
		$this->logChanges('Pico CMS custom plugin', $newCustomPlugins, $oldCustomPlugins);
	}
}
