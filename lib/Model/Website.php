<?php

namespace OCA\CMSPico\Model;

use OC\Files\Filesystem;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\CheckCharsException;
use OCA\CMSPico\Exceptions\MinCharsException;
use OCA\CMSPico\Exceptions\UserIsNotOwnerException;
use OCA\CMSPico\Exceptions\WebsiteIsPrivateException;
use OCA\CMSPico\Service\MiscService;
use OCP\IL10N;

class Website extends WebsiteCore {


	const TYPE_PUBLIC = 1;
	const TYPE_PRIVATE = 2;

	const SITE_LENGTH_MIN = 3;
	const NAME_LENGTH_MIN = 5;

	/** @var IL10N */
	private $l10n;

	/** @var string */
	private $viewer;

	/** @var string */
	private $templateSource;

	public function __construct() {
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);
		parent::__construct();
	}


	public function getAbsolutePath() {
		Filesystem::init($this->getUserId(), $this->getUserId() . '/files/');

		return Filesystem::getLocalFile($this->getPath());
	}


	/**
	 * @param string $viewer
	 */
	public function setViewer($viewer) {
		$this->viewer = $viewer;
	}

	/**
	 * @return string
	 */
	public function getViewer() {
		return $this->viewer;
	}


	/**
	 * @param string $source
	 */
	public function setTemplateSource($source) {
		$this->templateSource = $source;
	}

	/**
	 * @return string
	 */
	public function getTemplateSource() {
		return $this->templateSource;
	}


	/**
	 * @param $userId
	 *
	 * @throws UserIsNotOwnerException
	 */
	public function hasToBeOwnedBy($userId) {
		if ($this->getUserId() !== $userId) {
			throw new UserIsNotOwnerException($this->l10n->t('You are not the owner of this website'));
		}
	}


	public function userMustHaveAccess($userId) {
		if ($this->getOption('private') === '0') {
			return;
		}

		if ($this->getUserId() === $userId) {
			return;
		}

		throw new WebsiteIsPrivateException(
			$this->l10n->t('Website is private. You do not have access to this website')
		);
	}

	/**
	 * @throws CheckCharsException
	 * @throws MinCharsException
	 */
	public function hasToBeFilledWithValidEntries() {

		if (strlen($this->getSite()) < self::SITE_LENGTH_MIN) {
			throw new MinCharsException($this->l10n->t('The address of the website must be longer'));
		}

		if (strlen($this->getName()) < self::NAME_LENGTH_MIN) {
			throw new MinCharsException($this->l10n->t('The name of the website must be longer'));
		}

		if (MiscService::checkChars($this->getSite(), MiscService::ALPHA_NUMERIC_SCORES) === false) {
			throw new CheckCharsException(
				$this->l10n->t('The address of the website can only contains alpha numeric chars')
			);
		}
	}

}