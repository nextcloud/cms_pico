<?php

namespace OCA\CMSPico\Model;


class TemplateFile {

	/** @var string */
	private $base;

	/** @var string */
	private $filename;

	/** @var string */
	private $content;


	/**
	 * TemplateFile constructor.
	 *
	 * @param string $filename
	 * @param string $content
	 */
	function __construct($base, $filename, $content) {
		$this->base = $base;
		$this->filename = $filename;
		$this->content = $content;
	}


	/**
	 * @return string
	 */
	public function getFileName() {
		return $this->filename;
	}

	/**
	 * @param string $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}


	public function applyData($data) {
		$ak = array_keys($data);
		$temp = $this->getContent();
		foreach ($ak as $k) {
			$temp = str_replace('%%' . $k . '%%', $data[$k], $temp);
		}

		$this->setContent($temp);
	}
}