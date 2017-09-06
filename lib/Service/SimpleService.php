<?php


namespace OCA\CMSPico\Service;

class SimpleService {

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

}