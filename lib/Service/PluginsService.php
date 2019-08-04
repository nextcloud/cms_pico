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

namespace OCA\CMSPico\Service;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\PluginNotFoundException;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Model\Plugin;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;

class PluginsService
{
	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/**
	 * PluginsService constructor.
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
	 * @return array[]
	 */
	public function getPlugins(): array
	{
		return $this->getSystemPlugins() + $this->getCustomPlugins();
	}

	/**
	 * @return array[]
	 */
	public function getSystemPlugins(): array
	{
		$json = $this->configService->getAppValue(ConfigService::SYSTEM_PLUGINS);
		return $json ? json_decode($json, true) : [];
	}

	/**
	 * @return array[]
	 */
	public function getCustomPlugins(): array
	{
		$json = $this->configService->getAppValue(ConfigService::CUSTOM_PLUGINS);
		return $json ? json_decode($json, true) : [];
	}

	/**
	 * @return string[]
	 */
	public function getNewCustomPlugins(): array
	{
		$appDataPluginsFolder = $this->fileService->getAppDataFolder(PicoService::DIR_PLUGINS);
		$appDataPluginsFolder->sync(FolderInterface::SYNC_SHALLOW);

		$currentPlugins = $this->getPlugins();

		$newCustomPlugins = [];
		foreach ($appDataPluginsFolder->listing() as $pluginFolder) {
			$pluginName = $pluginFolder->getName();
			if ($pluginFolder->isFolder() && !isset($currentPlugins[$pluginName])) {
				$newCustomPlugins[] = $pluginName;
			}
		}

		return $newCustomPlugins;
	}

	/**
	 * @param string $pluginName
	 *
	 * @return Plugin
	 * @throws PluginNotFoundException
	 */
	public function publishSystemPlugin(string $pluginName): Plugin
	{
		$systemPluginsFolder = $this->fileService->getSystemFolder(PicoService::DIR_PLUGINS);
		$systemPluginsFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$systemPluginFolder = $systemPluginsFolder->get($pluginName);
			if (!$systemPluginFolder->isFolder()) {
				throw new PluginNotFoundException();
			}
		} catch (NotFoundException $e) {
			throw new PluginNotFoundException();
		}

		$plugins = $this->getSystemPlugins();
		$plugins[$pluginName] = $this->publishPlugin($systemPluginFolder, Plugin::PLUGIN_TYPE_SYSTEM);
		$this->configService->setAppValue(ConfigService::SYSTEM_PLUGINS, json_encode($plugins));

		return $plugins[$pluginName];
	}

	/**
	 * @param string $pluginName
	 *
	 * @return Plugin
	 * @throws PluginNotFoundException
	 */
	public function publishCustomPlugin(string $pluginName): Plugin
	{
		$appDataPluginsFolder = $this->fileService->getAppDataFolder(PicoService::DIR_PLUGINS);
		$appDataPluginsFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$appDataPluginFolder = $appDataPluginsFolder->get($pluginName);
			if (!$appDataPluginFolder->isFolder()) {
				throw new PluginNotFoundException();
			}
		} catch (NotFoundException $e) {
			throw new PluginNotFoundException();
		}

		$plugins = $this->getCustomPlugins();
		$plugins[$pluginName] = $this->publishPlugin($appDataPluginFolder, Plugin::PLUGIN_TYPE_CUSTOM);
		$this->configService->setAppValue(ConfigService::CUSTOM_PLUGINS, json_encode($plugins));

		return $plugins[$pluginName];
	}

	/**
	 * @param FolderInterface $pluginSourceFolder
	 * @param int             $pluginType
	 *
	 * @return Plugin
	 */
	private function publishPlugin(FolderInterface $pluginSourceFolder, int $pluginType): Plugin
	{
		$publicPluginsFolder = $this->fileService->getPublicFolder(PicoService::DIR_PLUGINS);

		$pluginName = $pluginSourceFolder->getName();
		$pluginSourceFolder->sync();

		try {
			$pluginFolder = $publicPluginsFolder->get($pluginName);
			if (!$pluginFolder->isFolder()) {
				throw new InvalidPathException();
			}

			$pluginFolder->delete();
		} catch (NotFoundException $e) {}

		$pluginFolder = $pluginSourceFolder->copy($publicPluginsFolder);
		return new Plugin($pluginFolder, $pluginType);
	}

	/**
	 * @param string $plugin
	 */
	public function depublishCustomPlugin(string $plugin)
	{
		$publicPluginsFolder = $this->fileService->getPublicFolder(PicoService::DIR_PLUGINS);

		try {
			$publicPluginsFolder->get($plugin)->delete();
		} catch (NotFoundException $e) {}

		$customPlugins = $this->getCustomPlugins();
		unset($customPlugins[$plugin]);
		$this->configService->setAppValue(ConfigService::CUSTOM_PLUGINS, json_encode($customPlugins));
	}

	/**
	 * @return string
	 */
	public function getPluginsPath(): string
	{
		$appPath = \OC_App::getAppPath(Application::APP_NAME);
		return $appPath . '/appdata_public/' . PicoService::DIR_PLUGINS . '/';
	}

	/**
	 * @return string
	 */
	public function getPluginsUrl(): string
	{
		return \OC_App::getAppWebPath(Application::APP_NAME) . '/appdata_public/' . PicoService::DIR_PLUGINS . '/';
	}
}
