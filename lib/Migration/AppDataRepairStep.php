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

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\PicoService;
use OCA\CMSPico\Service\PluginsService;
use OCA\CMSPico\Service\ThemesService;
use OCP\Files\NotFoundException;
use OCP\ILogger;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AppDataRepairStep implements IRepairStep
{
	/** @var ILogger */
	private $logger;

	/** @var ThemesService */
	private $themesService;

	/** @var PluginsService */
	private $pluginsService;

	/** @var FileService */
	private $fileService;

	/**
	 * AppDataRepairStep constructor.
	 *
	 * @param ILogger        $logger
	 * @param ThemesService  $themesService
	 * @param PluginsService $pluginsService
	 * @param FileService    $fileService
	 */
	public function __construct(
		ILogger $logger,
		ThemesService $themesService,
		PluginsService $pluginsService,
		FileService $fileService
	) {
		$this->logger = $logger;
		$this->themesService = $themesService;
		$this->pluginsService = $pluginsService;
		$this->fileService = $fileService;
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
	public function run(IOutput $output)
	{
		$this->log('Copying Pico CMS config …');
		$this->copyConfig();

		$this->log('Copying Pico CMS templates …');
		$this->copyTemplates();

		$this->log('Publishing Pico CMS themes …');
		$this->publishThemes();

		$this->log('Publishing Pico CMS plugins …');
		$this->publishPlugins();
	}

	/**
	 * @return void
	 */
	private function copyConfig()
	{
		$appDataConfigFolder = $this->fileService->getAppDataFolder(PicoService::DIR_CONFIG);
		$systemConfigFolder = $this->fileService->getSystemFolder(PicoService::DIR_CONFIG);

		foreach ($systemConfigFolder->listing() as $configFile) {
			$configFileName = $configFile->getName();

			if (!$configFile->isFile()) {
				continue;
			}

			try {
				$appDataConfigFolder->get($configFileName)->delete();
				$this->log(sprintf('Replacing %s "%s"', 'config file', $configFileName), ILogger::WARN);
			} catch (NotFoundException $e) {
				$this->log(sprintf('Adding %s "%s"', 'config file', $configFileName));
			}

			$configFile->copy($appDataConfigFolder);
		}
	}

	/**
	 * @return void
	 */
	private function copyTemplates()
	{
		$appDataTemplatesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_TEMPLATES);
		$systemTemplatesFolder = $this->fileService->getSystemFolder(PicoService::DIR_TEMPLATES);

		foreach ($systemTemplatesFolder->listing() as $templateFolder) {
			$templateFileName = $templateFolder->getName();

			if (!$templateFolder->isFolder()) {
				continue;
			}

			try {
				$appDataTemplatesFolder->get($templateFileName)->delete();
				$this->log(sprintf('Replacing %s "%s"', 'template', $templateFileName), ILogger::WARN);
			} catch (NotFoundException $e) {
				$this->log(sprintf('Adding %s "%s"', 'template', $templateFileName));
			}

			$templateFolder->copy($appDataTemplatesFolder);
		}
	}

	/**
	 * @return void
	 */
	private function publishThemes()
	{
		$publicThemesFolder = $this->fileService->getPublicFolder(PicoService::DIR_THEMES);
		$publicThemesFolder->empty();

		$systemThemesFolder = $this->fileService->getSystemFolder(PicoService::DIR_THEMES);
		foreach ($this->themesService->getSystemThemes() as $themeName) {
			$systemThemesFolder->get($themeName)->copy($publicThemesFolder);
		}

		$appDataThemesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_THEMES);
		foreach ($this->themesService->getCustomThemes() as $themeName) {
			$appDataThemesFolder->get($themeName)->copy($publicThemesFolder);
		}
	}

	/**
	 * @return void
	 */
	private function publishPlugins()
	{
		$publicPluginsFolder = $this->fileService->getPublicFolder(PicoService::DIR_PLUGINS);
		$publicPluginsFolder->empty();

		$systemPluginsFolder = $this->fileService->getSystemFolder(PicoService::DIR_PLUGINS);
		foreach ($this->pluginsService->getSystemPlugins() as $pluginName) {
			$systemPluginsFolder->get($pluginName)->copy($publicPluginsFolder);
		}

		$appDataPluginsFolder = $this->fileService->getAppDataFolder(PicoService::DIR_PLUGINS);
		foreach ($this->pluginsService->getCustomPlugins() as $pluginName) {
			$appDataPluginsFolder->get($pluginName)->copy($publicPluginsFolder);
		}
	}

	/**
	 * @param string $message
	 * @param int    $level
	 */
	private function log(string $message, int $level = ILogger::DEBUG)
	{
		$this->logger->log($level, $message, [ 'app' => Application::APP_NAME ]);
	}
}
