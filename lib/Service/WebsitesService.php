<?php


namespace OCA\CMSPico\Service;

use OCA\CMSPico\Db\WebsitesRequest;
use OCA\CMSPico\Exceptions\WebsiteAlreadyExistException;
use OCA\CMSPico\Exceptions\WebsiteDoesNotExistException;
use OCA\CMSPico\Model\Webpage;
use OCA\CMSPico\Model\Website;
use OCP\IL10N;

class WebsitesService {

	/** @var IL10N */
	private $l10n;

	/** @var WebsitesRequest */
	private $websiteRequest;

	/** @var TemplatesService */
	private $templatesService;

	/** @var PicoService */
	private $picoService;

	/** @var MiscService */
	private $miscService;

	/**
	 * SimpleService constructor.
	 *
	 * @param IL10N $l10n
	 * @param WebsitesRequest $websiteRequest
	 * @param TemplatesService $templatesService
	 * @param PicoService $picoService
	 * @param MiscService $miscService
	 */
	function __construct(
		IL10N $l10n, WebsitesRequest $websiteRequest, TemplatesService $templatesService,
		PicoService $picoService, MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->websiteRequest = $websiteRequest;
		$this->templatesService = $templatesService;
		$this->picoService = $picoService;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $userId
	 * @param $name
	 * @param string $site
	 * @param string $path
	 *
	 * @throws WebsiteAlreadyExistException
	 */
	public function createWebsite($name, $userId, $site, $path) {
		$website = new Website();
		$website->setName($name)
				->setUserId($userId)
				->setSite($site)
				->setPath($path)
				->setTemplateSource(TemplatesService::TEMPLATE_DEFAULT);

		try {
			$website->hasToBeFilledWithValidEntries();
			$website = $this->websiteRequest->getWebsiteFromSite($website->getSite());
			throw new WebsiteAlreadyExistException(
				$this->l10n->t('Website already exist. Please choose another one.')
			);
		} catch (WebsiteDoesNotExistException $e) {
		}

		$this->templatesService->installTemplates($website);
		$this->websiteRequest->create($website);
	}


	/**
	 * @param int $siteId
	 *
	 * @return Website
	 */
	public function getWebsiteFromId($siteId) {
		return $this->websiteRequest->getWebsiteFromId($siteId);
	}


	/**
	 * @param $website
	 */
	public function updateWebsite($website) {
		$this->websiteRequest->update($website);

	}

	/**
	 * @param string $userId
	 *
	 * @return Website[]
	 */
	public function getWebsitesFromUser($userId) {
		$websites = $this->websiteRequest->getWebsitesFromUserId($userId);

		return $websites;
	}


	/**
	 * @param string $site
	 * @param string $viewer
	 *
	 * @return string
	 */
	public function getWebpageFromSite($site, $viewer) {

		$website = $this->websiteRequest->getWebsiteFromSite($site);
		$website->setViewer($viewer);

		return $this->picoService->getContent($website);
	}


}