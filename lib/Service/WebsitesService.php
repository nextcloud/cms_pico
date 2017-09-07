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

	/** @var string */
	private $userId;

	/** @var WebsitesRequest */
	private $websiteRequest;

	/** @var PicoService */
	private $picoService;

	/** @var MiscService */
	private $miscService;

	/**
	 * SimpleService constructor.
	 *
	 * @param IL10N $l10n
	 * @param $userId
	 * @param WebsitesRequest $websiteRequest
	 * @param PicoService $picoService
	 * @param MiscService $miscService
	 */
	function __construct(
		IL10N $l10n, $userId, WebsitesRequest $websiteRequest, PicoService $picoService,
		MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->userId = $userId;
		$this->websiteRequest = $websiteRequest;
		$this->picoService = $picoService;
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


	/**
	 * @param string $site
	 * @param string $page
	 * @param string $viewer
	 *
	 * @return string
	 */
	public function getWebpageFromSite($site, $page, $viewer) {

		if (substr($page, 0, 1) !== '/') {
			$page = '/' . $page;
		}

		if ($page === '/') {
			$page = Webpage::DEFAULT_ROOT;
		}

		$website = $this->websiteRequest->getWebsiteFromSite($site);
		$website->setViewer($viewer);

		return $this->getWebpage($website, $page);
	}


	public function getWebpage(Website $website, $page) {

		$webpage = new Webpage($website, $page);
		$webpage->hasToExist();
		$content = '>' . $webpage->getContent();

		$content = $this->picoService->parseContent($content);
//		Filesystem::init($website->getUserId(), $website->getUserId() . '/files/');
//		$localPath = Filesystem::getLocalFile($website->getPath() . $page);


//$content = Filesystem::getView()->file_get_contents($website->getPath() . $page));

		return $content;
//		return $website;

	}
}