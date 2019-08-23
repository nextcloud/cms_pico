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

use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Files\FileInterface;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\StorageFolder;
use OCA\CMSPico\Model\TemplateFile;
use OCA\CMSPico\Model\Website;
use OCP\Files\NotFoundException;

class TemplatesService
{
	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/**
	 * TemplatesService constructor.
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
	 * check if template exist.
	 *
	 * @param string $template
	 *
	 * @throws TemplateNotFoundException
	 */
	public function assertValidTemplate($template)
	{
		if (!in_array($template, $this->getTemplates())) {
			throw new TemplateNotFoundException();
		}
	}

	/**
	 * @return string[]
	 */
	public function getTemplates(): array
	{
		return array_merge($this->getSystemTemplates(), $this->getCustomTemplates());
	}

	/**
	 * @return string[]
	 */
	public function getSystemTemplates(): array
	{
		$systemTemplatesFolder = $this->fileService->getSystemFolder(PicoService::DIR_TEMPLATES);
		$systemTemplatesFolder->sync(FolderInterface::SYNC_SHALLOW);

		$systemTemplates = [];
		foreach ($systemTemplatesFolder as $templateFolder) {
			if ($templateFolder->isFolder()) {
				$systemTemplates[] = $templateFolder->getName();
			}
		}

		return $systemTemplates;
	}

	/**
	 * @return string[]
	 */
	public function getCustomTemplates(): array
	{
		$json = $this->configService->getAppValue(ConfigService::CUSTOM_TEMPLATES);
		return $json ? json_decode($json, true) : [];
	}

	/**
	 * @return string[]
	 */
	public function getNewCustomTemplates(): array
	{
		$currentTemplates = $this->getTemplates();

		$customTemplatesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_TEMPLATES);
		$customTemplatesFolder->sync(FolderInterface::SYNC_SHALLOW);

		$newTemplates = [];
		foreach ($customTemplatesFolder as $templateFolder) {
			$template = $templateFolder->getName();
			if ($templateFolder->isFolder() && !in_array($template, $currentTemplates)) {
				$newTemplates[] = $template;
			}
		}

		return $newTemplates;
	}

	/**
	 * @param Website $website
	 *
	 * @throws TemplateNotFoundException
	 */
	public function installTemplate(Website $website)
	{
		$userFolder = new StorageFolder(\OC::$server->getUserFolder($website->getUserId()));

		try {
			$userFolder->get($website->getPath());

			// website folder exists; since we don't want to
			// mess around with a user's files, bail out
			return;
		} catch (NotFoundException $e) {
			// proceed if the website folder doesn't exist yet
		}

		$websiteFolder = $userFolder->newFolder($website->getPath());

		$templateFolder = $this->getTemplateFolder($website);
		$templateFolder->sync();

		$templateData = $this->getTemplateData($website);
		foreach (new \RecursiveIteratorIterator($templateFolder) as $file) {
			/** @var FileInterface $file */
			$templateFile = new TemplateFile($file);

			try {
				$targetFolder = $websiteFolder->getFolder($templateFile->getParent());
			} catch (NotFoundException $e) {
				$targetFolder = $websiteFolder->newFolder($templateFile->getParent());
			}

			if ($templateFile->getName() === 'empty') {
				continue;
			}

			$templateFile->setTemplateData($templateData);
			$templateFile->copy($targetFolder);
		}
	}

	/**
	 * @param Website $website
	 *
	 * @return array<string,string>
	 */
	private function getTemplateData(Website $website): array
	{
		return [
			'site_title' => $website->getName()
		];
	}

	/**
	 * @param Website $website
	 *
	 * @return FolderInterface
	 * @throws TemplateNotFoundException
	 */
	public function getTemplateFolder(Website $website): FolderInterface
	{
		$templateName = $website->getTemplateSource();
		if (!$templateName) {
			throw new TemplateNotFoundException();
		}

		$systemTemplatesFolder = $this->fileService->getSystemFolder(PicoService::DIR_TEMPLATES);
		$systemTemplatesFolder->sync(FolderInterface::SYNC_SHALLOW);

		$customTemplatesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_TEMPLATES);
		$customTemplatesFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$templateFolder = $systemTemplatesFolder->getFolder($templateName);
		} catch (NotFoundException $e) {
			try {
				$templateFolder = $customTemplatesFolder->getFolder($templateName);
			} catch (NotFoundException $e) {
				throw new TemplateNotFoundException();
			}
		}

		return $templateFolder->fakeRoot();
	}
}
