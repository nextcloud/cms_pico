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
use OCA\CMSPico\Exceptions\PluginAlreadyExistsException;
use OCA\CMSPico\Exceptions\PluginNotFoundException;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\LocalFolder;
use OCA\CMSPico\Model\DummyPluginFile;
use OCA\CMSPico\Model\Plugin;
use OCP\Files\AlreadyExistsException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;

class PluginsService
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
	 * PluginsService constructor.
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
		foreach ($appDataPluginsFolder as $pluginFolder) {
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
	 * @throws PluginAlreadyExistsException
	 */
	public function publishSystemPlugin(string $pluginName): Plugin
	{
		if (!$pluginName) {
			throw new PluginNotFoundException($pluginName);
		}

		$systemPluginsFolder = $this->fileService->getSystemFolder(PicoService::DIR_PLUGINS);
		$systemPluginsFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$systemPluginFolder = $systemPluginsFolder->getFolder($pluginName);
		} catch (NotFoundException $e) {
			throw new PluginNotFoundException($pluginName, $e);
		}

		$plugins = $this->getSystemPlugins();
		$plugins[$pluginName] = $this->publishPlugin($systemPluginFolder, Plugin::TYPE_SYSTEM);
		$this->configService->setAppValue(ConfigService::SYSTEM_PLUGINS, json_encode($plugins));

		return $plugins[$pluginName];
	}

	/**
	 * @param string $pluginName
	 *
	 * @return Plugin
	 * @throws PluginNotFoundException
	 * @throws PluginAlreadyExistsException
	 */
	public function publishCustomPlugin(string $pluginName): Plugin
	{
		if (!$pluginName) {
			throw new PluginNotFoundException($pluginName);
		}

		$systemPlugins = $this->getSystemPlugins();
		if (isset($systemPlugins[$pluginName])) {
			throw new PluginAlreadyExistsException($pluginName);
		}

		$appDataPluginsFolder = $this->fileService->getAppDataFolder(PicoService::DIR_PLUGINS);
		$appDataPluginsFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$appDataPluginFolder = $appDataPluginsFolder->getFolder($pluginName);
		} catch (NotFoundException $e) {
			throw new PluginNotFoundException($pluginName, $e);
		}

		$plugins = $this->getCustomPlugins();
		$plugins[$pluginName] = $this->publishPlugin($appDataPluginFolder, Plugin::TYPE_CUSTOM);
		$this->configService->setAppValue(ConfigService::CUSTOM_PLUGINS, json_encode($plugins));

		return $plugins[$pluginName];
	}

	/**
	 * @param FolderInterface $pluginSourceFolder
	 * @param int             $pluginType
	 *
	 * @return Plugin
	 * @throws PluginAlreadyExistsException
	 */
	private function publishPlugin(FolderInterface $pluginSourceFolder, int $pluginType): Plugin
	{
		$publicPluginsFolder = $this->getPluginsFolder(true);

		$pluginName = $pluginSourceFolder->getName();
		$pluginSourceFolder->sync();

		try {
			$publicPluginsFolder->getFolder($pluginName);
			throw new PluginAlreadyExistsException($pluginName);
		} catch (NotFoundException $e) {
			// in fact we want the plugin not to exist yet
		}

		/** @var LocalFolder $pluginFolder */
		$pluginFolder = $pluginSourceFolder->copy($publicPluginsFolder);
		return new Plugin($pluginFolder, $pluginType);
	}

	/**
	 * @param string $pluginName
	 *
	 * @throws PluginNotFoundException
	 */
	public function depublishCustomPlugin(string $pluginName): void
	{
		if (!$pluginName) {
			throw new PluginNotFoundException($pluginName);
		}

		$publicPluginsFolder = $this->getPluginsFolder();

		try {
			$publicPluginsFolder->getFolder($pluginName)->delete();
		} catch (NotFoundException $e) {
			throw new PluginNotFoundException($pluginName, $e);
		}

		$customPlugins = $this->getCustomPlugins();
		unset($customPlugins[$pluginName]);
		$this->configService->setAppValue(ConfigService::CUSTOM_PLUGINS, json_encode($customPlugins));
	}

	/**
	 * @param string $pluginName
	 *
	 * @return Plugin
	 * @throws PluginNotFoundException
	 * @throws PluginAlreadyExistsException
	 */
	public function copyDummyPlugin(string $pluginName): Plugin
	{
		if (!$pluginName) {
			throw new PluginNotFoundException($pluginName);
		}

		$systemPlugins = $this->getSystemPlugins();
		$customPlugins = $this->getCustomPlugins();

		if (isset($systemPlugins[$pluginName]) || isset($customPlugins[$pluginName])) {
			throw new PluginAlreadyExistsException($pluginName);
		}

		$systemPluginsFolder = $this->fileService->getSystemFolder(PicoService::DIR_PLUGINS);
		$appDataPluginsFolder = $this->fileService->getAppDataFolder(PicoService::DIR_PLUGINS);

		try {
			$basePluginFile = $systemPluginsFolder->getFile('DummyPlugin.php');
		} catch (NotFoundException $e) {
			throw new PluginNotFoundException('DummyPlugin', $e);
		}

		try {
			$pluginFile = new DummyPluginFile($pluginName, $basePluginFile);
			$pluginFolder = $appDataPluginsFolder->newFolder($pluginName);
			$pluginFile->copy($pluginFolder);
		} catch (InvalidPathException $e) {
			throw new PluginNotFoundException($pluginName, $e);
		} catch (AlreadyExistsException $e) {
			throw new PluginAlreadyExistsException($pluginName, $e);
		}

		return $this->publishCustomPlugin($pluginName);
	}

	/**
	 * @param bool $renewETag
	 * @param bool $forceRenewETag
	 *
	 * @return LocalFolder
	 */
	public function getPluginsFolder(bool $renewETag = false, bool $forceRenewETag = false): LocalFolder
	{
		$pluginsBaseFolder = $this->fileService->getPublicFolder(PicoService::DIR_PLUGINS);

		/** @var LocalFolder $pluginsFolder */
		$pluginsFolder = null;

		$pluginsETag = $this->configService->getAppValue(ConfigService::PLUGINS_ETAG);
		if ($pluginsETag) {
			$pluginsFolder = $pluginsBaseFolder->getFolder($pluginsETag);
		}

		if (($renewETag && !$this->renewedETag) || $forceRenewETag || !$pluginsFolder) {
			$pluginsETag = $this->miscService->getRandom();

			if ($pluginsFolder) {
				$pluginsFolder = $pluginsFolder->rename($pluginsETag);
			} else {
				$pluginsFolder = $pluginsBaseFolder->newFolder($pluginsETag);
			}

			$this->configService->setAppValue(ConfigService::PLUGINS_ETAG, $pluginsETag);
			$this->renewedETag = true;
		}

		return $pluginsFolder->fakeRoot();
	}

	/**
	 * @return string
	 */
	public function getPluginsPath(): string
	{
		$appPath = Application::getAppPath() . '/';
		$pluginsPath = 'appdata_public/' . PicoService::DIR_PLUGINS . '/';
		$pluginsETag = $this->configService->getAppValue(ConfigService::PLUGINS_ETAG);
		return $appPath . $pluginsPath . ($pluginsETag ? $pluginsETag . '/' : '');
	}

	/**
	 * @return string
	 */
	public function getPluginsUrl(): string
	{
		$appWebPath = Application::getAppWebPath() . '/';
		$pluginsPath = 'appdata_public/' . PicoService::DIR_PLUGINS . '/';
		$pluginsETag = $this->configService->getAppValue(ConfigService::PLUGINS_ETAG);
		return $appWebPath . $pluginsPath . ($pluginsETag ? $pluginsETag . '/' : '');
	}
}
