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

use OC\App\AppManager;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Files\FolderInterface;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;

class PluginsService
{
	/** @var AppManager */
	private $appManager;

	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/**
	 * PluginsService constructor.
	 *
	 * @param AppManager    $appManager
	 * @param ConfigService $configService
	 * @param FileService   $fileService
	 */
	public function __construct(AppManager $appManager, ConfigService $configService, FileService $fileService)
	{
		$this->appManager = $appManager;
		$this->configService = $configService;
		$this->fileService = $fileService;
	}

	/**
	 * @return string[]
	 */
	public function getPlugins(): array
	{
		return array_merge($this->getSystemPlugins(), $this->getCustomPlugins());
	}

	/**
	 * @return string[]
	 */
	public function getSystemPlugins(): array
	{
		/** @var FolderInterface $systemPluginsFolder */
		$systemPluginsFolder = $this->fileService->getSystemFolder()->get(PicoService::DIR_PLUGINS);
		if (!$systemPluginsFolder->isFolder()) {
			throw new InvalidPathException();
		}

		$systemPlugins = [];
		foreach ($systemPluginsFolder->listing() as $pluginFolder) {
			if ($pluginFolder->isFolder()) {
				$systemPlugins[] = $pluginFolder->getName();
			}
		}

		return $systemPlugins;
	}

	/**
	 * @return string[]
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
		$currentPlugins = $this->getPlugins();

		/** @var FolderInterface $customPluginsFolder */
		$customPluginsFolder = $this->fileService->getAppDataFolder()->get(PicoService::DIR_PLUGINS);
		if (!$customPluginsFolder->isFolder()) {
			throw new InvalidPathException();
		}

		$customPluginsFolder->sync(FolderInterface::SYNC_SHALLOW);

		$newCustomPlugins = [];
		foreach ($customPluginsFolder->listing() as $pluginFolder) {
			$plugin = $pluginFolder->getName();
			if ($pluginFolder->isFolder() && !in_array($plugin, $currentPlugins)) {
				$newCustomPlugins[] = $plugin;
			}
		}

		return $newCustomPlugins;
	}

	/**
	 * @param string $plugin
	 */
	public function publishCustomPlugin(string $plugin)
	{
		$publicFolder = $this->fileService->getPublicFolder();
		$publicPluginsFolder = $publicFolder->get(PicoService::DIR_PLUGINS);

		$appDataFolder = $this->fileService->getAppDataFolder();

		/** @var FolderInterface $appDataPluginFolder */
		$appDataPluginFolder = $appDataFolder->get(PicoService::DIR_PLUGINS . '/' . $plugin);
		$appDataPluginFolder->sync();

		$appDataPluginFolder->copy($publicPluginsFolder);
	}

	/**
	 * @param string $plugin
	 */
	public function depublishCustomPlugin(string $plugin)
	{
		$publicFolder = $this->fileService->getPublicFolder();

		try {
			$publicFolder->get(PicoService::DIR_PLUGINS . '/' . $plugin)->delete();
		} catch (NotFoundException $e) {}
	}

	/**
	 * @return string
	 */
	public function getPluginsPath(): string
	{
		$appPath = $this->appManager->getAppPath(Application::APP_NAME);
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
