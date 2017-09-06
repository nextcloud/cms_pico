<?php


namespace OCA\CMSPico\Service;

use OCA\CMSPico\Db\WebsitesRequest;
use OCA\CMSPico\Exceptions\WebsiteAlreadyExistException;
use OCA\CMSPico\Exceptions\WebsiteDoesNotExistException;
use OCA\CMSPico\Model\Website;
use OCP\IL10N;

class WebsitesService {

	/** @var WebsitesRequest */
	private $websiteRequest;

	/** @var IL10N */
	private $l10n;

	/** @var MiscService */
	private $miscService;

	/**
	 * SimpleService constructor.
	 *
	 * @param WebsitesRequest $websiteRequest
	 * @param IL10N $l10n
	 * @param MiscService $miscService
	 */
	function __construct(WebsitesRequest $websiteRequest, IL10N $l10n, MiscService $miscService) {
		$this->websiteRequest = $websiteRequest;
		$this->l10n = $l10n;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $userId
	 * @param string $site
	 * @param string $path
	 *
	 * @throws WebsiteAlreadyExistException
	 */
	public function createWebsite($userId, $site, $path) {
		$website = new Website();
		$website->setUserId($userId)
				->setSite($site)
				->setPath($path);
$this->miscService->log('### ' . json_encode($website));
		try {
			$website = $this->websiteRequest->getWebsiteFromSite($website->getSite());
			throw new WebsiteAlreadyExistException(
				$this->l10n->t('Website already exist. Please choose another one.')
			);
		} catch (WebsiteDoesNotExistException $e) {
		}

		$this->websiteRequest->create($website);
	}


	/**
	 * @param string $userId
	 *
	 * @return Website[]
	 */
	public function getWebsitesFromUser($userId) {
		$websites = $this->websiteRequest->getWebsitesFromUserId($userId);
		$this->miscService->log('#### ' . json_encode($websites));

		return $websites;
	}


}