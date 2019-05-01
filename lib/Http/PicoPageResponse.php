<?php

declare(strict_types=1);

namespace OCA\CMSPico\Http;

use OCA\CMSPico\Model\PicoPage;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\Http\Response;

class PicoPageResponse extends Response
{
	/** @var PicoPage */
	private $page;

	/**
	 * PicoPageResponse constructor.
	 *
	 * @param PicoPage $page
	 */
	public function __construct(PicoPage $page)
	{
		$this->page = $page;

		$this->addHeader('Content-Disposition', 'inline; filename=""');
		$this->setContentSecurityPolicy(new PicoContentSecurityPolicy());
	}

	/**
	 * @param EmptyContentSecurityPolicy $csp
	 *
	 * @return $this
	 */
	public function setContentSecurityPolicy(EmptyContentSecurityPolicy $csp) : self
	{
		if (!($csp instanceof PicoContentSecurityPolicy)) {
			// Pico really needs its own CSP...
			return $this;
		}

		parent::setContentSecurityPolicy($csp);
		return $this;
	}

	/**
	 * @return string
	 */
	public function render() : string
	{
		return $this->page->render();
	}
}
