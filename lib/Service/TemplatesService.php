<?php


namespace OCA\CMSPico\Service;

use DirectoryIterator;
use OCA\CMSPico\Model\TemplateFile;
use OCA\CMSPico\Model\Website;

class TemplatesService {

	const TEMPLATE_DEFAULT = 'sample_pico';
	const TEMPLATE_DIR = __DIR__ . '/../../templates/';

	/** @var MiscService */
	private $miscService;

	/** @var array */
	private $data = [];

	/**
	 * TemplatesService constructor.
	 *
	 * @param MiscService $miscService
	 */
	function __construct(MiscService $miscService) {
		$this->miscService = $miscService;

		$this->data['site_title'] = 'TITRE !!';
	}


	/**
	 * @param Website $website
	 */
	public function installTemplates(Website $website) {

		$files = $this->getSourceFiles(self::TEMPLATE_DIR . $website->getTemplateSource() . '/');

		foreach ($files as $file) {
			$file->applyData($this->data);
			$this->miscService->log('@@@ ' . $file->getFileName() . $file->getContent());
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
				$files =
					array_merge($files, $this->getSourceFiles($base, $directory . $file->getFilename()));
				continue;
			}

			$content = file_get_contents($base . $directory . $file->getFilename());
			$files[] = new TemplateFile($base, $directory . $file->getFilename(), $content);
		}

		return $files;
	}


}