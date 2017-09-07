<?php


namespace OCA\CMSPico\Service;

use OCA\CMSPico\Model\Webpage;
use OCA\CMSPico\Model\Website;
use Pico;

class PicoService {

	const DIR_CONFIG = 'config/';
	const DIR_PLUGINS = 'plugins/';
	const DIR_THEMES = 'themes/';

	/** @var MiscService */
	private $miscService;

	/**
	 * PicoService constructor.
	 *
	 * @param MiscService $miscService
	 */
	function __construct(MiscService $miscService) {
		$this->miscService = $miscService;
	}


	public function getContent(Website $website) {
		$pico = new Pico(
			$website->getAbsolutePath(),
			self::DIR_CONFIG, self::DIR_PLUGINS, self::DIR_THEMES
		);

		$pico->run();

		return $pico->getFileContent();
	}

}