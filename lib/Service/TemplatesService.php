<?php


namespace OCA\CMSPico\Service;

use DirectoryIterator;
use Exception;
use OCA\CMSPico\Exceptions\WriteAccessException;
use OCA\CMSPico\Model\TemplateFile;
use OCA\CMSPico\Model\Website;
use OCP\Files\Folder;
use OCP\IL10N;

class TemplatesService {

	const TEMPLATE_DEFAULT = 'sample_pico';
	const TEMPLATE_DIR = __DIR__ . '/../../templates/';

	/** @var IL10N */
	private $l10n;

	/** @var MiscService */
	private $miscService;

	/** @var Folder */
	private $websiteFolder;

	/**
	 * TemplatesService constructor.
	 *
	 * @param IL10N $l10n
	 * @param MiscService $miscService
	 */
	function __construct(IL10N $l10n, MiscService $miscService) {
		$this->l10n = $l10n;
		$this->miscService = $miscService;
	}


	/**
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
	 * @param $base
	 * @param string $dir
	 *
	 * @return TemplateFile[]
	 */
	private function getSourceFiles($base, $dir = '') {

		MiscService::endSlash($base);
		MiscService::endSlash($dir);

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

	private function initFolder($path) {

		if (!$this->websiteFolder->nodeExists($path)) {
			$this->websiteFolder->newFolder($path);
		}
	}


}