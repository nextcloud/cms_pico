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
use OCA\CMSPico\Service\ThemesService;
use OCP\Files\InvalidPathException;
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

	/** @var FileService */
	private $fileService;

	/**
	 * AppDataRepairStep constructor.
	 *
	 * @param ILogger          $logger
	 * @param ThemesService    $themesService
	 * @param FileService      $fileService
	 */
	public function __construct(ILogger $logger, ThemesService $themesService, FileService $fileService)
	{
		$this->logger = $logger;
		$this->themesService = $themesService;
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
		$this->logger->debug('Copying Pico CMS config…', [ 'app' => Application::APP_NAME ]);
		$this->copyConfig();

		$this->logger->debug('Copying Pico CMS templates…', [ 'app' => Application::APP_NAME ]);
		$this->copyTemplates();

		$this->logger->debug('Publishing Pico CMS themes…', [ 'app' => Application::APP_NAME ]);
		$this->publishThemes();
	}

	/**
	 * @return void
	 */
	private function copyConfig()
	{
		$appDataFolder = $this->fileService->getAppDataFolder();

		try {
			$appDataConfigFolder = $appDataFolder->get(PicoService::DIR_CONFIG);
		} catch (NotFoundException $e) {
			$appDataConfigFolder = $appDataFolder->newFolder(PicoService::DIR_CONFIG);
		}

		/** @var FolderInterface $systemConfigFolder */
		$systemConfigFolder = $this->fileService->getSystemFolder()->get(PicoService::DIR_CONFIG);
		if (!$systemConfigFolder->isFolder()) {
			throw new InvalidPathException();
		}

		foreach ($systemConfigFolder->listing() as $configFile) {
			$configFileName = $configFile->getName();

			if (!$configFile->isFile()) {
				continue;
			}

			try {
				$appDataConfigFolder->get($configFileName)->delete();
			} catch (NotFoundException $e) {}

			$configFile->copy($appDataConfigFolder);
		}
	}

	/**
	 * @return void
	 */
	private function copyTemplates()
	{
		$appDataFolder = $this->fileService->getAppDataFolder();

		try {
			$appDataTemplatesFolder = $appDataFolder->get(PicoService::DIR_TEMPLATES);
		} catch (NotFoundException $e) {
			$appDataTemplatesFolder = $appDataFolder->newFolder(PicoService::DIR_TEMPLATES);
		}

		/** @var FolderInterface $systemTemplatesFolder */
		$systemTemplatesFolder = $this->fileService->getSystemFolder()->get(PicoService::DIR_TEMPLATES);
		if (!$systemTemplatesFolder->isFolder()) {
			throw new InvalidPathException();
		}

		foreach ($systemTemplatesFolder->listing() as $templateFolder) {
			$template = $templateFolder->getName();

			if (!$templateFolder->isFolder()) {
				continue;
			}

			try {
				$appDataTemplatesFolder->get($template)->delete();
			} catch (NotFoundException $e) {}

			$templateFolder->copy($appDataTemplatesFolder);
		}
	}

	/**
	 * @return void
	 */
	private function publishThemes()
	{
		$publicFolder = $this->fileService->getPublicFolder();

		try {
			$publicFolder->get(PicoService::DIR_THEMES)->delete();
		} catch (NotFoundException $e) {}

		$publicThemesFolder = $publicFolder->newFolder(PicoService::DIR_THEMES);

		$systemFolder = $this->fileService->getSystemFolder();
		foreach ($this->themesService->getSystemThemes() as $theme) {
			$systemFolder->get(PicoService::DIR_THEMES . '/' . $theme)->copy($publicThemesFolder);
		}

		$appDataFolder = $this->fileService->getAppDataFolder();
		foreach ($this->themesService->getCustomThemes() as $theme) {
			$appDataFolder->get(PicoService::DIR_THEMES . '/' . $theme)->copy($publicThemesFolder);
		}

		if (!$appDataFolder->exists(PicoService::DIR_THEMES)) {
			$appDataFolder->newFolder(PicoService::DIR_THEMES);
		}
	}
}
