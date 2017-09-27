<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\CMSPico\Service;

use DirectoryIterator;
use Exception;
use OCA\CMSPico\Exceptions\TemplateDoesNotExistException;
use OCA\CMSPico\Exceptions\WriteAccessException;
use OCA\CMSPico\Model\TemplateFile;
use OCA\CMSPico\Model\Website;
use OCP\Files\Folder;
use OCP\IL10N;

class TemplatesService {

	const TEMPLATES = ['sample_pico', 'empty'];
	const TEMPLATE_DIR = __DIR__ . '/../../Pico/templates/';

	/** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var Folder */
	private $websiteFolder;

	/**
	 * TemplatesService constructor.
	 *
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	function __construct(IL10N $l10n, ConfigService $configService, MiscService $miscService) {
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * check if template exist.
	 *
	 * @param string $template
	 *
	 * @throws TemplateDoesNotExistException
	 */
	public function templateHasToExist($template) {
		if (!in_array($template, $this->getTemplatesList())) {
			throw new TemplateDoesNotExistException($this->l10n->t('Template does not exist'));
		}
	}


	/**
	 * returns all templates available to users.
	 *
	 * @param bool $customOnly
	 *
	 * @return array
	 */
	public function getTemplatesList($customOnly = false) {
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
	 * returns theme from the Pico/templates/ dir that are not available yet to users.
	 *
	 * @return array
	 */
	public function getNewTemplatesList() {

		$newTemplates = [];
		$currTemplates = $this->getTemplatesList();
		$allTemplates = $this->getDirectoriesFromTemplatesDir();
		foreach ($allTemplates as $template) {
			if (!in_array($template, $currTemplates)) {
				$newTemplates[] = $template;
			}
		}

		return $newTemplates;
	}


	/**
	 * returns custom templates from Pico/templates/
	 *
	 * @return array
	 */
	private function getDirectoriesFromTemplatesDir() {

		$allTemplates = [];
		foreach (new DirectoryIterator(self::TEMPLATE_DIR) as $file) {

			if (!$file->isDir() || substr($file->getFilename(), 0, 1) === '.') {
				continue;
			}

			$allTemplates[] = $file->getFilename();
		}

		return $allTemplates;
	}


	/**
	 * Install templates into a new website.
	 * Templates will be parsed and formatted in the process.
	 *
	 * @param Website $website
	 */
	public function installTemplates(Website $website) {

		$files = $this->getSourceFiles(self::TEMPLATE_DIR . $website->getTemplateSource() . '/');

		$this->initWebsiteFolder($website);
		$data = $this->generateData($website);
		foreach ($files as $file) {
			$file->applyData($data);
			$this->generateFile($file, $website);
		}
	}


	/**
	 * @param string $base
	 * @param string $dir
	 *
	 * @return TemplateFile[]
	 */
	private function getSourceFiles($base, $dir = '') {

		$base = MiscService::endSlash($base);
		$dir = MiscService::endSlash($dir);

		$files = [];
		foreach (new DirectoryIterator($base . $dir) as $file) {

			if (substr($file->getFilename(), 0, 1) === '.') {
				continue;
			}

			if ($file->isDir()) {
				$files = array_merge($files, $this->getSourceFiles($base, $dir . $file->getFilename()));
				continue;
			}

			$files[] = new TemplateFile($base, $dir . $file->getFilename());
		}

		return $files;
	}


	/**
	 * @param TemplateFile $file
	 * @param Website $website
	 *
	 * @throws WriteAccessException
	 */
	private function generateFile(TemplateFile $file, Website $website) {
		try {
			$this->initFolder(pathinfo($website->getPath() . $file->getFileName(), PATHINFO_DIRNAME));

			$new = $this->websiteFolder->newFile($website->getPath() . $file->getFileName());
			$new->putContent($file->getContent());
		} catch (Exception $e) {
			throw new WriteAccessException(
				$this->l10n->t('Cannot generate template file in this folder')
			);
		}

	}


	/**
	 * @param Website $website
	 */
	private function initWebsiteFolder(Website $website) {
		$this->websiteFolder = \OC::$server->getUserFolder($website->getUserId());
		$this->initFolder($website->getPath());
	}


	/**
	 * @param Website $website
	 *
	 * @return array
	 */
	private function generateData(Website $website) {
		return [
			'site_title' => $website->getName(),
			'base_url'   => \OC::$WEBROOT . $website->getSite()
		];
	}


	/**
	 * @param $path
	 */
	private function initFolder($path) {

		if (!$this->websiteFolder->nodeExists($path)) {
			$this->websiteFolder->newFolder($path);
		}
	}


}