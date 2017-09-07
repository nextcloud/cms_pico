<?php


namespace OCA\CMSPico\Service;

class TemplatesService {

	const TEMPLATE_DEFAULT = 'sample_pico';

	/** @var MiscService */
	private $miscService;

	/**
	 * TemplatesService constructor.
	 *
	 * @param MiscService $miscService
	 */
	function __construct(MiscService $miscService) {
		$this->miscService = $miscService;
	}



	public function installTemplates($source = self::TEMPLATE_DEFAULT)
	{



	}
}