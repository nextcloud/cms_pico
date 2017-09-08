<?php


namespace OCA\CMSPico\Service;

use DirectoryIterator;
use OCA\CMSPico\Model\TemplateFile;
use OCA\CMSPico\Model\Website;
use OCP\Files\Folder;

class TemplatesService {

	const TEMPLATE_DEFAULT = 'sample_pico';
	const TEMPLATE_DIR = __DIR__ . '/../../templates/';

	/** @var MiscService */
	private $miscService;

	/** @var Folder */
	private $websiteFolder;

	/**
	 * TemplatesService constructor.
	 *
	 * @param MiscService $miscService
	 */
	function __construct(MiscService $miscService) {
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
	 * @param string $directory
	 *
	 * @return TemplateFile[]
	 */
	private function getSourceFiles($base, $directory = '') {

		MiscService::endSlash($base);
		MiscService::endSlash($directory);

		$files = [];
		foreach (new DirectoryIterator($base . $directory) as $file) {
			if (substr($file->getFilename(), 0, 1) === '.') {
				continue;
			}

			if ($file->isDir()) {
				$files = array_merge(
					$files, $this->getSourceFiles($base, $directory . $file->getFilename())
				);
				continue;
			}

			$content = file_get_contents($base . $directory . $file->getFilename());
			$files[] = new TemplateFile($base, $directory . $file->getFilename(), $content);
		}

		return $files;
	}


	/**
	 * @param TemplateFile $file
	 * @param Website $website
	 */
	private function generateFile(TemplateFile $file, Website $website) {
		$this->initFolder(pathinfo($website->getPath() . $file->getFileName(), PATHINFO_DIRNAME));
		$new = $this->websiteFolder->newFile($website->getPath() . $file->getFileName());
		$new->putContent($file->getContent());
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