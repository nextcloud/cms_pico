<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\CMSPico\Model;

use Exception;
use OC\Files\View;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\CheckCharsException;
use OCA\CMSPico\Exceptions\ContentDirIsNotLocalException;
use OCA\CMSPico\Exceptions\MinCharsException;
use OCA\CMSPico\Exceptions\PathContainSpecificFoldersException;
use OCA\CMSPico\Exceptions\UserIsNotOwnerException;
use OCA\CMSPico\Exceptions\WebpageDoesNotExistException;
use OCA\CMSPico\Exceptions\WebpageIsNotReadableException;
use OCA\CMSPico\Exceptions\WebsiteIsPrivateException;
use OCA\CMSPico\Service\MiscService;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IL10N;

class Website extends WebsiteCore {


	const TYPE_PUBLIC = 1;
	const TYPE_PRIVATE = 2;

	const SITE_LENGTH_MIN = 3;
	const NAME_LENGTH_MIN = 5;

	/** @var IL10N */
	private $l10n;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var View */
	private $ownerView;


	/**
	 * Website constructor.
	 *
	 * @param string $data
	 */
	public function __construct($data = '') {
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);
		$this->rootFolder = \OC::$server->getRootFolder();

		parent::__construct($data);
	}


	/**
	 *
	 */
	private function initSiteOwnerView() {

		if ($this->ownerView !== null) {
			return;
		}

		$this->ownerView = new View($this->getUserId() . '/files/');
	}


	/**
	 * @param string $path
	 * @param bool $end
	 *
	 * @return string
	 */
	public function getAbsolutePath($path = '', $end = true) {
		if ($path === '') {
			$path = $this->getPath();
		}

		$this->initSiteOwnerView();
		$path = $this->ownerView->getLocalFile($path);

		if ($end === false) {
			return $path;
		}

		return MiscService::endSlash($path);
	}


	/**
	 * @param string $local
	 *
	 * @throws WebpageIsNotReadableException
	 */
	private function hasToBeReadableByViewer($local = '') {

		$fileId = $this->getPageFileId($local);
		$viewerFiles = $this->rootFolder->getUserFolder($this->getViewer())
										->getById($fileId);

		foreach ($viewerFiles as $file) {
			if ($file->isReadable()) {
				return;
			}
		}

		throw new WebpageIsNotReadableException();
	}


	/**
	 * @param string $local
	 *
	 * @return int
	 * @throws WebpageDoesNotExistException
	 */
	public function getPageFileId($local = '') {

		try {
			$ownerFile = $this->rootFolder->getUserFolder($this->getUserId())
										  ->get($this->getPath() . $local);

			return $ownerFile->getId();
		} catch (NotFoundException $e) {
			throw new WebpageDoesNotExistException($this->l10n->t('Webpage does not exist'));
		}
	}


	/**
	 * @param string $userId
	 *
	 * @throws UserIsNotOwnerException
	 */
	public function hasToBeOwnedBy($userId) {
		if ($this->getUserId() !== $userId) {
			throw new UserIsNotOwnerException($this->l10n->t('You are not the owner of this website'));
		}
	}


	/**
	 * @param string $path
	 *
	 * @throws ContentDirIsNotLocalException
	 */
	public function contentMustBeLocal($path) {

		try {
			$this->pathCantContainSpecificFolders($path);
			if (strpos($path, $this->getAbsolutePath()) !== 0) {
				throw new PathContainSpecificFoldersException();
			}

		} catch (PathContainSpecificFoldersException $e) {
			throw new ContentDirIsNotLocalException($this->l10n->t('Content Directory is not valid.'));
		}
	}


	/**
	 * @param string $path
	 *
	 * @return bool|string
	 */
	public function getRelativePath($path) {
		if (substr($path, 0, 1) !== '/') {
			return $path;
		}

		return substr($path, strlen($this->getAbsolutePath()));
	}


	/**
	 * @param string $path
	 * @param array $meta
	 *
	 * @throws WebsiteIsPrivateException
	 */
	public function viewerMustHaveAccess($path, $meta = []) {

		try {
			if ($this->pageIsPublic($meta)) {
				return;
			}

			if ($this->getViewer() === $this->getUserId()) {
				return;
			}

			$this->hasToBeReadableByViewer($path);

		} catch (Exception $e) {
			throw new WebsiteIsPrivateException(
				$this->l10n->t('Website is private. You do not have access to this website')
			);
		}
	}


	/**
	 * @param array $meta
	 *
	 * @return bool
	 */
	private function pageIsPublic($meta) {

		if (key_exists('access', $meta) && strtolower($meta['access']) === 'private') {
			return false;
		}

		if ($this->getOption('private') === '1') {
			return false;
		}

		return true;
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
	 * @param string $path
	 *
	 * @throws PathContainSpecificFoldersException
	 */
	public function pathCantContainSpecificFolders($path = '') {
		if ($path === '') {
			$path = $this->getPath();
		}

		$limit = ['.', '..'];

		$folders = explode('/', $path);
		foreach ($folders as $folder) {
			if (in_array($folder, $limit)) {
				throw new PathContainSpecificFoldersException(
					$this->l10n->t('Path is malformed, please check.')
				);
			}
		}
	}
}