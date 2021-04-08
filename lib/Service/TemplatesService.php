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

use OCA\CMSPico\Exceptions\TemplateAlreadyExistsException;
use OCA\CMSPico\Exceptions\TemplateNotCompatibleException;
use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Files\FileInterface;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\StorageFolder;
use OCA\CMSPico\Model\Template;
use OCA\CMSPico\Model\TemplateFile;
use OCA\CMSPico\Model\Website;
use OCP\Files\AlreadyExistsException;
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
	 * @param string $templateName
	 *
	 * @throws TemplateNotFoundException
	 * @throws TemplateNotCompatibleException
	 */
	public function assertValidTemplate(string $templateName): void
	{
		$templates = $this->getTemplates();

		if (!isset($templates[$templateName])) {
			throw new TemplateNotFoundException();
		}

		if (!$templates[$templateName]['compat']) {
			throw new TemplateNotCompatibleException(
				$templateName,
				$templates[$templateName]['compatReason'],
				$templates[$templateName]['compatReasonData']
			);
		}
	}

	/**
	 * @return array[]
	 */
	public function getTemplates(): array
	{
		return $this->getSystemTemplates() + $this->getCustomTemplates();
	}

	/**
	 * @return array[]
	 */
	public function getSystemTemplates(): array
	{
		$json = $this->configService->getAppValue(ConfigService::SYSTEM_TEMPLATES);
		return $json ? json_decode($json, true) : [];
	}

	/**
	 * @return array[]
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
		$customTemplatesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_TEMPLATES);
		$customTemplatesFolder->sync(FolderInterface::SYNC_SHALLOW);

		$currentTemplates = $this->getTemplates();

		$newCustomTemplates = [];
		foreach ($customTemplatesFolder as $templateFolder) {
			$templateName = $templateFolder->getName();
			if ($templateFolder->isFolder() && !isset($currentTemplates[$templateName])) {
				$newCustomTemplates[] = $templateName;
			}
		}

		return $newCustomTemplates;
	}

	/**
	 * @param string $templateName
	 *
	 * @return Template
	 * @throws TemplateNotFoundException
	 * @throws TemplateAlreadyExistsException
	 */
	public function registerSystemTemplate(string $templateName): Template
	{
		if (!$templateName) {
			throw new TemplateNotFoundException();
		}

		$systemTemplatesFolder = $this->fileService->getSystemFolder(PicoService::DIR_TEMPLATES);
		$systemTemplatesFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$templateFolder = $systemTemplatesFolder->getFolder($templateName);
		} catch (NotFoundException $e) {
			throw new TemplateNotFoundException();
		}

		$templates = $this->getSystemTemplates();
		$templates[$templateName] = new Template($templateFolder, Template::TYPE_SYSTEM);
		$this->configService->setAppValue(ConfigService::SYSTEM_TEMPLATES, json_encode($templates));

		return $templates[$templateName];
	}

	/**
	 * @param string $templateName
	 *
	 * @return Template
	 * @throws TemplateNotFoundException
	 * @throws TemplateAlreadyExistsException
	 */
	public function registerCustomTemplate(string $templateName): Template
	{
		if (!$templateName) {
			throw new TemplateNotFoundException();
		}

		$systemTemplates = $this->getSystemTemplates();
		if (isset($systemTemplates[$templateName])) {
			throw new TemplateAlreadyExistsException();
		}

		$appDataTemplatesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_TEMPLATES);
		$appDataTemplatesFolder->sync(FolderInterface::SYNC_SHALLOW);

		try {
			$templateFolder = $appDataTemplatesFolder->getFolder($templateName);
		} catch (NotFoundException $e) {
			throw new TemplateNotFoundException();
		}

		$templates = $this->getCustomTemplates();
		$templates[$templateName] = new Template($templateFolder, Template::TYPE_CUSTOM);
		$this->configService->setAppValue(ConfigService::CUSTOM_TEMPLATES, json_encode($templates));

		return $templates[$templateName];
	}

	/**
	 * @param string $templateName
	 *
	 * @throws TemplateNotFoundException
	 */
	public function removeCustomTemplate(string $templateName): void
	{
		if (!$templateName) {
			throw new TemplateNotFoundException();
		}

		$customTemplates = $this->getCustomTemplates();
		unset($customTemplates[$templateName]);
		$this->configService->setAppValue(ConfigService::CUSTOM_TEMPLATES, json_encode($customTemplates));
	}

	/**
	 * @param string $baseTemplateName
	 * @param string $templateName
	 *
	 * @return Template
	 * @throws TemplateNotFoundException
	 * @throws TemplateAlreadyExistsException
	 */
	public function copyTemplate(string $baseTemplateName, string $templateName): Template
	{
		if (!$baseTemplateName || !$templateName) {
			throw new TemplateNotFoundException();
		}

		$systemTemplates = $this->getSystemTemplates();
		$customTemplates = $this->getCustomTemplates();

		if (isset($systemTemplates[$templateName]) || isset($customTemplates[$templateName])) {
			throw new TemplateAlreadyExistsException();
		}

		$baseTemplateFolder = $this->getTemplateFolder($baseTemplateName);
		$appDataTemplatesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_TEMPLATES);

		try {
			$baseTemplateFolder->copy($appDataTemplatesFolder, $templateName);
		} catch (AlreadyExistsException $e) {
			throw new TemplateAlreadyExistsException();
		}

		return $this->registerCustomTemplate($templateName);
	}

	/**
	 * @param Website $website
	 * @param string  $templateName
	 *
	 * @throws TemplateNotFoundException
	 */
	public function installTemplate(Website $website, string $templateName): void
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

		$templateFolder = $this->getTemplateFolder($templateName);
		$templateFolder->sync();

		$templateData = $this->getTemplateData($website);
		foreach (new \RecursiveIteratorIterator($templateFolder) as $file) {
			/** @var FileInterface $file */
			$templateFile = new TemplateFile($file);

			try {
				$targetFolder = $websiteFolder->getFolder($templateFile->getParentPath());
			} catch (NotFoundException $e) {
				$targetFolder = $websiteFolder->newFolder($templateFile->getParentPath());
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
	 * @param string $templateName
	 *
	 * @return FolderInterface
	 * @throws TemplateNotFoundException
	 */
	public function getTemplateFolder(string $templateName): FolderInterface
	{
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
