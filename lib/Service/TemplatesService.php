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

namespace OCA\CMSPico\Service;

use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Files\FileInterface;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\StorageFolder;
use OCA\CMSPico\Model\TemplateFile;
use OCA\CMSPico\Model\Website;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\IL10N;

class TemplatesService
{
	const TEMPLATES = ['sample_pico', 'empty'];

	/** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/** @var MiscService */
	private $miscService;

	/**
	 * TemplatesService constructor.
	 *
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param FileService $fileService
	 * @param MiscService $miscService
	 */
	function __construct(
		IL10N $l10n,
		ConfigService $configService,
		FileService $fileService,
		MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->fileService = $fileService;
		$this->miscService = $miscService;
	}

	/**
	 * check if template exist.
	 *
	 * @param string $template
	 *
	 * @throws TemplateNotFoundException
	 */
	public function assertValidTemplate($template) {
		if (!in_array($template, $this->getTemplatesList())) {
			throw new TemplateNotFoundException();
		}
	}

	/**
	 * returns all templates available to users.
	 *
	 * @param bool $customOnly
	 *
	 * @return array
	 */
	public function getTemplatesList($customOnly = false)
	{
		$templates = [];
		if ($customOnly !== true) {
			$templates = self::TEMPLATES;
		}

		$customs = json_decode($this->configService->getAppValue(ConfigService::CUSTOM_TEMPLATES), true);
		if ($customs !== null) {
			$templates = array_merge($templates, $customs);
		}

		return $templates;
	}

	/**
	 * @return string[]
	 */
	public function getNewTemplatesList(): array
	{
		$currentTemplates = $this->getTemplatesList();

		/** @var FolderInterface $appDataTemplatesFolder */
		$appDataTemplatesFolder = $this->fileService->getAppDataFolder()->get(PicoService::DIR_TEMPLATES);
		if (!$appDataTemplatesFolder->isFolder()) {
			throw new InvalidPathException();
		}

		$newTemplates = [];
		foreach ($appDataTemplatesFolder->listing() as $templateFolder) {
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
	public function installTemplates(Website $website)
	{
		$filesIterator = function (FolderInterface $folder, string $basePath = '') use (&$filesIterator) {
			$files = [];
			foreach ($folder->listing() as $node) {
				if ($node->isFolder()) {
					/** @var FolderInterface $node */
					$files += $filesIterator($node, $basePath . '/' . $node->getName());
				} else {
					/** @var FileInterface $node */
					$files[$basePath . '/' . $node->getName()] = $node->getContent();
				}
			}

			return $files;
		};

		$websitePath = $website->getPath();
		$templateFile = $website->getTemplateSource();

		$systemFolder = $this->fileService->getSystemFolder();
		$appDataFolder = $this->fileService->getAppDataFolder();
		$userFolder = new StorageFolder(\OC::$server->getUserFolder($website->getUserId()));

		try {
			/** @var FolderInterface $templateFolder */
			$templateFolder = $systemFolder->get(PicoService::DIR_TEMPLATES . '/' . $templateFile);
			if (!$templateFolder->isFolder()) {
				throw new NotFoundException();
			}
		} catch (NotFoundException $e) {
			try {
				/** @var FolderInterface $templateFolder */
				$templateFolder = $appDataFolder->get(PicoService::DIR_TEMPLATES . '/' . $templateFile);
				if (!$templateFolder->isFolder()) {
					throw new NotFoundException();
				}
			} catch (NotFoundException $e) {
				throw new TemplateNotFoundException();
			}
		}

		try {
			$websiteFolder = $userFolder->get($websitePath);
		} catch (NotFoundException $e) {
			$websiteFolder = $userFolder->newFolder($websitePath);
		}

		$websiteData = $this->generateWebsiteData($website);
		foreach ($filesIterator($templateFolder) as $templateFilePath => $templateData) {
			$templateFile = new TemplateFile($templateFilePath, $templateData);

			try {
				$targetFolder = $websiteFolder->get($templateFile->getParent());
			} catch (NotFoundException $e) {
				$targetFolder = $websiteFolder->newFolder($templateFile->getParent());
			}

			if ($templateFile->getName() === 'empty') {
				continue;
			}

			$templateFile->applyWebsiteData($websiteData);
			$templateFile->copy($targetFolder);
		}
	}

	/**
	 * @param Website $website
	 *
	 * @return array<string,string>
	 */
	private function generateWebsiteData(Website $website): array
	{
		return [
			'site_title' => $website->getName()
		];
	}
}
