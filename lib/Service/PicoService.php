<?php


namespace OCA\CMSPico\Service;

use Exception;
use OCA\CMSPico\Model\Webpage;
use OCA\CMSPico\Model\Website;
use Pico;

class PicoService {

	const DIR_CONFIG = 'config/';
	const DIR_PLUGINS = 'plugins/';
	const DIR_THEMES = 'themes/';

	private $userId;
	/** @var MiscService */
	private $miscService;

	/**
	 * PicoService constructor.
	 *
	 * @param string $userId
	 * @param MiscService $miscService
	 */
	function __construct($userId, MiscService $miscService) {
		$this->userId = $userId;
		$this->miscService = $miscService;
	}


	public function getContent(Website $website) {

		$website->userMustHaveAccess($this->userId);

		$pico = new Pico(
			$website->getAbsolutePath(),
			self::DIR_CONFIG, self::DIR_PLUGINS, self::DIR_THEMES
		);

		$pico->run();

		return $pico->getFileContent();
	}

}