<?php

declare(strict_types=1);

namespace OCA\CMSPico\Model;

use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Service\PicoService;
use Pico;

class PicoPage
{
	/** @var Pico */
	private $pico;

	/** @var Website */
	private $website;

	/** @var string */
	private $output;

	public function __construct(Website $website, Pico $pico, string $output)
	{
		$this->website = $website;
		$this->pico = $pico;
		$this->output = $output;
	}

	/**
	 * @return string
	 */
	public function getAbsolutePath() : string
	{
		$absolutePath = $this->pico->getRequestFile();
		if ($absolutePath) {
			return $absolutePath;
		}

		return $this->website->getAbsolutePath(PicoService::DIR_CONTENT . '/' . $this->website->getPage());
	}

	/**
	 * @return string
	 */
	public function getRelativePath() : string
	{
		$absolutePath = $this->pico->getRequestFile();
		if ($absolutePath) {
			try {
				return $this->website->getRelativePagePath($absolutePath);
			} catch (PageInvalidPathException $e) {
				// silently ignore this exception, proceed
			}
		}

		return $this->website->getPage();
	}

	/**
	 * @return string
	 */
	public function getRawContent() : string
	{
		return $this->pico->getRawContent();
	}

	/**
	 * @return array
	 */
	public function getMeta() : array
	{
		return $this->pico->getFileMeta();
	}

	/**
	 * @return string
	 */
	public function getContent() : string
	{
		return $this->pico->getFileContent();
	}

	/**
	 * @return string
	 */
	public function render() : string
	{
		return $this->output;
	}
}
