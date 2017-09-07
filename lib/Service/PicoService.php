<?php


namespace OCA\CMSPico\Service;

use Pico;

class PicoService {

	/** @var MiscService */
	private $miscService;

	/**
	 * SimpleService constructor.
	 *
	 * @param MiscService $miscService
	 */
	function __construct(MiscService $miscService) {
		$this->miscService = $miscService;
	}





	public function parseContent($content)
	{
		$pico = new Pico('/home/maxence/sites/nextcloud/server/data/cult/files/qwe/', 'config/', 'plugins/', 'themes/');
$pico->run();

return	'>>>> ' .	$pico->getRawContent();
	//	return $content;

	}

}