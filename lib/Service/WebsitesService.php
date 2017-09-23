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

namespace OCA\CMSPico\Service;

use Exception;
use OC\Encryption\Manager;
use OC\Server;
use OCA\CMSPico\Db\WebsitesRequest;
use OCA\CMSPico\Exceptions\EncryptedFilesystemException;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\WebsiteAlreadyExistException;
use OCA\CMSPico\Exceptions\WebsiteDoesNotExistException;
use OCA\CMSPico\Model\Website;
use OCP\IL10N;

class WebsitesService {

	/** @var IL10N */
	private $l10n;

	/** @var Manager */
	private $encryptionManager;

	/** @var WebsitesRequest */
	private $websiteRequest;

	/** @var TemplatesService */
	private $templatesService;

	/** @var PicoService */
	private $picoService;

	/** @var MiscService */
	private $miscService;

	/**
	 * WebsitesService constructor.
	 *
	 * @param IL10N $l10n
	 * @param WebsitesRequest $websiteRequest
	 * @param TemplatesService $templatesService
	 * @param PicoService $picoService
	 * @param MiscService $miscService
	 *
	 * @internal param Manager $encryptionManager
	 */
	function __construct(
		IL10N $l10n, WebsitesRequest $websiteRequest, TemplatesService $templatesService,
		PicoService $picoService, MiscService $miscService
	) {

		$this->l10n = $l10n;
		$this->encryptionManager = \OC::$server->getEncryptionManager();
		$this->websiteRequest = $websiteRequest;
		$this->templatesService = $templatesService;
		$this->picoService = $picoService;
		$this->miscService = $miscService;
	}


	/**
	 * createWebsite();
	 *
	 * create website using the templates file.
	 * We check that the template exists and that the inputs are valid.
	 *
	 * @param string $name
	 * @param string $userId
	 * @param string $site
	 * @param string $path
	 * @param string $template
	 *
	 * @throws WebsiteAlreadyExistException
	 */
	public function createWebsite($name, $userId, $site, $path, $template) {
		$this->templatesService->templateHasToExist($template);

		$website = new Website();
		$website->setName($name)
				->setUserId($userId)
				->setSite($site)
				->setPath($path)
				->setTemplateSource($template);

		try {
			$website->hasToBeFilledWithValidEntries();
			$website = $this->websiteRequest->getWebsiteFromSite($website->getSite());
			throw new WebsiteAlreadyExistException($this->l10n->t('Website already exist.'));
		} catch (WebsiteDoesNotExistException $e) {
			// In fact we want the website to not exist (yet).
		}

		$this->templatesService->installTemplates($website);
		$this->websiteRequest->create($website);
	}


	/**
	 * deleteWebsite();
	 *
	 * Delete a website regarding its Id and the userId
	 *
	 * @param int $siteId
	 * @param string $userId
	 */
	public function deleteWebsite($siteId, $userId) {

		$website = $this->getWebsiteFromId($siteId);
		$website->hasToBeOwnedBy($userId);

		$this->forceDeleteWebsite($website);
	}


	/**
	 * forceDeleteWebsite();
	 *
	 * delete a website.
	 *
	 * Warning: this method does not check the ownership of the website.
	 * Please use deleteWebsite().
	 *
	 * @param Website $website
	 */
	public function forceDeleteWebsite(Website $website) {
		$this->websiteRequest->delete($website);
	}


	/**
	 * Event onUserRemoved();
	 *
	 * Delete all website from the removed user.
	 *
	 * @param string $userId
	 */
	public function onUserRemoved($userId) {
		$this->websiteRequest->deleteAllFromUser($userId);
	}


	/**
	 * getWebsiteFromId();
	 *
	 * returns the website from its Id.
	 *
	 * @param int $siteId
	 *
	 * @return Website
	 */
	public function getWebsiteFromId($siteId) {
		return $this->websiteRequest->getWebsiteFromId($siteId);
	}


	/**
	 * updateWebsite();
	 *
	 * update a Website.
	 *
	 * @param Website $website
	 */
	public function updateWebsite(Website $website) {
		$this->websiteRequest->update($website);

	}

	/**
	 * getWebsitesFromUser();
	 *
	 * returns all website from a user.
	 *
	 * @param string $userId
	 *
	 * @return Website[]
	 */
	public function getWebsitesFromUser($userId) {
		$websites = $this->websiteRequest->getWebsitesFromUserId($userId);

		return $websites;
	}


	/**
	 * getWebsiteFromSite();
	 *
	 * returns website regarding its keyword/site.
	 *
	 * @param string $site
	 *
	 * @return Website
	 */
	public function getWebsiteFromSite($site) {

		$website = $this->websiteRequest->getWebsiteFromSite($site);

		return $website;
	}


	/**
	 * getWebpageFromSite();
	 *
	 * Get the correct Page from Pico regarding its keyword/site.
	 * We assign a viewer (current user) to manage private webpages.
	 *
	 * @param string $site
	 * @param string $viewer
	 * @param $page
	 *
	 * @return string
	 * @throws Exception
	 * @throws PicoRuntimeException
	 */
	public function getWebpageFromSite($site, $viewer, $page) {

		try {
			$website = $this->websiteRequest->getWebsiteFromSite($site);
			$website->setViewer($viewer);
			$website->setPage($page);

			if ($this->encryptionManager->isEnabled()) {
				throw new EncryptedFilesystemException('cms_pico does not support encrypted filesystem');
			}

			return $this->picoService->getContent($website);
		} catch (PicoRuntimeException $e) {
			$this->miscService->log('Webpage cannot be rendered - ' . $e->getMessage());
			throw new PicoRuntimeException("Webpage cannot be rendered");
		} catch (Exception $e) {
			throw $e;
		}

	}


}