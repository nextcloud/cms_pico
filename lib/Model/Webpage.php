<?php

namespace OCA\CMSPico\Model;

use OC\Files\Filesystem;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\WebpageDoesNotExistException;
use OCA\CMSPico\Exceptions\WebsiteDoesNotExistException;
use OCP\IL10N;

class Webpage {

	const DEFAULT_ROOT = '/index.txt';

	const TYPE_PUBLIC = 1;
	const TYPE_PRIVATE = 2;

	/** @var IL10N */
	private $l10n;

	/** @var Website */
	private $website;

	/** @var string */
	private $pagePath;

	/** @var string */
	private $content;

	/** @var int */
	private $type = self::TYPE_PUBLIC;

	/** @var bool */
	private $pageExists = false;

	public function __construct(Website $website, $path) {
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);

		$this->website = $website;
		$this->pagePath = $path;

		Filesystem::init($website->getUserId(), $website->getUserId() . '/files/');
		$localPath = Filesystem::getLocalFile($website->getPath() . $this->pagePath);

		$this->pageExists = true;
		$this->setContent(Filesystem::getView()->file_get_contents($website->getPath() . $this->pagePath));
	}


	public function hasToExist() {
		if ($this->pageExists === false)
			throw new WebpageDoesNotExistException($this->l10n->t('404 Not found'));
	}

	/**
	 * @return Website
	 */
	public function getWebsite() {
		return $this->website;
	}


	/**
	 * @return string
	 */
	public function getPagePath() {
		return $this->pagePath;
	}


	/**
	 * @param int $type
	 *
	 * @return $this
	 */
	public function setType($type) {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * @param string $content
	 *
	 * @return $this
	 */
	public function setContent($content) {
		$this->content = $content;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}


}