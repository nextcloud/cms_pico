<?php

namespace OCA\CMSPico\Model;

use OC\Files\Filesystem;
use OC\Files\View;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\CheckCharsException;
use OCA\CMSPico\Exceptions\MinCharsException;
use OCA\CMSPico\Exceptions\PathContainSpecificFoldersException;
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

	/** @var View */
	private $ownerView = null;

	/** @var View */
	private $viewerView = null;


	public function __construct() {
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);
		parent::__construct();
	}


	/**
	 * init View
	 */
	private function initSiteViewerView() {
		if ($this->viewerView !== null) {
			return;
		}

		$this->viewerView = new View($this->getViewer() . '/files/');
	}


	/**
	 * init View
	 */
	private function initSiteOwnerView() {
		if ($this->ownerView !== null) {
			return;
		}

		$this->ownerView = new View($this->getUserId() . '/files/');
	}


	/**
	 * @return string
	 */
	public function getAbsolutePath() {
		$this->initSiteOwnerView();

		return $this->ownerView->getLocalFile($this->getPath());
	}


	/**
	 * @param string $page
	 *
	 * @return false|\OC\Files\FileInfo
	 */
	public function getPageInfo($page = '') {
		$this->initSiteViewerView();

		return $this->viewerView->getFileInfo($this->getPath() . $page);
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


	/**
	 * @throws WebsiteIsPrivateException
	 */
	public function viewerMustHaveAccess() {
		if ($this->getViewer() === $this->getUserId()) {
			return;
		}

		if ($this->getOption('private') !== '1') {
			return;
		}

		if ($this->itemIsSharedToViewer('')) {
			return;
		}

		throw new WebsiteIsPrivateException(
			$this->l10n->t('Website is private. You do not have access to this website')
		);
	}


	/**
	 * @param string $item
	 *
	 * @return bool
	 */
	private function itemIsSharedToViewer($item = '') {

		$info = $this->getPageInfo($item);
		if ($info === false) {
			return false;
		}

		if ($info->isShared()) {
			return true;
		}

		return false;
	}


	/**
	 * @throws CheckCharsException
	 * @throws MinCharsException
	 * @throws PathContainSpecificFoldersException
	 */
	public function hasToBeFilledWithValidEntries() {

		$this->hasToBeFilledWithNonEmptyValues();
		$this->pathCantContainSpecificFolders();

		if (MiscService::checkChars($this->getSite(), MiscService::ALPHA_NUMERIC_SCORES) === false) {
			throw new CheckCharsException(
				$this->l10n->t('The address of the website can only contains alpha numeric chars')
			);
		}
	}


	/**
	 * @throws MinCharsException
	 */
	private function hasToBeFilledWithNonEmptyValues() {
		if (strlen($this->getSite()) < self::SITE_LENGTH_MIN) {
			throw new MinCharsException($this->l10n->t('The address of the website must be longer'));
		}

		if (strlen($this->getName()) < self::NAME_LENGTH_MIN) {
			throw new MinCharsException($this->l10n->t('The name of the website must be longer'));
		}
	}


	/**
	 * this is overkill - NC does not allow to create directory outside of the users' filesystem
	 * Not sure that there is a single use for this security check
	 *
	 * @throws PathContainSpecificFoldersException
	 */
	private function pathCantContainSpecificFolders() {
		$limit = ['.', '..'];

		$folders = explode('/', $this->getPath());
		foreach ($folders as $folder) {
			if (in_array($folder, $limit)) {
				throw new PathContainSpecificFoldersException(
					$this->l10n->t('Path is malformed, please check.')
				);
			}
		}
	}
}