<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2017, Maxence Lange (<maxence@artificial-owl.com>)
 * @copyright Copyright (c) 2019, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
 *
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
 */

declare(strict_types=1);

namespace OCA\CMSPico\Controller;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\TemplateNotCompatibleException;
use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Exceptions\ThemeNotCompatibleException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteExistsException;
use OCA\CMSPico\Exceptions\WebsiteForeignOwnerException;
use OCA\CMSPico\Exceptions\WebsiteInvalidDataException;
use OCA\CMSPico\Exceptions\WebsiteInvalidOwnerException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;

class WebsitesController extends Controller
{
	use ControllerTrait;
	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var WebsitesService */
	private $websitesService;

	/**
	 * WebsitesController constructor.
	 *
	 * @param IRequest        $request
	 * @param string          $userId
	 * @param IL10N           $l10n
	 * @param ILogger         $logger
	 * @param WebsitesService $websitesService
	 */
	public function __construct(
		IRequest $request,
		$userId,
		IL10N $l10n,
		ILogger $logger,
		WebsitesService $websitesService
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->websitesService = $websitesService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getPersonalWebsites(): DataResponse
	{
		$data = [ 'websites' => $this->websitesService->getWebsitesFromUser($this->userId) ];
		return new DataResponse($data, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param array<string,string> $data
	 *
	 * @return DataResponse
	 */
	public function createPersonalWebsite(array $data): DataResponse
	{
		try {
			$website = (new Website())
				->setName($data['name'] ?? '')
				->setUserId($this->userId)
				->setSite($data['site'] ?? '')
				->setTheme($data['theme'] ?? '')
				->setPath($data['path'] ?? '')
				->setTemplateSource($data['template'] ?? '');

			$this->websitesService->createWebsite($website);

			return $this->getPersonalWebsites();
		} catch (\Exception $e) {
			$error = [];
			if ($e instanceof WebsiteExistsException) {
				$error['error'] = [ 'field' => 'site', 'message' => $this->l10n->t('Website exists.') ];
			} elseif ($e instanceof WebsiteInvalidOwnerException) {
				$error['error'] = [ 'field' => 'user', 'message' => $this->l10n->t('No permission.') ];
			} elseif (($e instanceof WebsiteInvalidDataException) && $e->getField()) {
				$error['error'] = [ 'field' => $e->getField(), 'message' => $e->getMessage() ];
			} elseif ($e instanceof ThemeNotFoundException) {
				$error['error'] = [ 'field' => 'theme', 'message' => $this->l10n->t('Theme not found.') ];
			} elseif ($e instanceof ThemeNotCompatibleException) {
				$error['error'] = [ 'field' => 'theme', 'message' => $this->l10n->t($e->getReason()) ];
			} elseif ($e instanceof TemplateNotFoundException) {
				$error['error'] = [ 'field' => 'template', 'message' => $this->l10n->t('Template not found.') ];
			} elseif ($e instanceof TemplateNotCompatibleException) {
				$error['error'] = [ 'field' => 'template', 'message' => $this->l10n->t($e->getReason()) ];
			}

			return $this->createErrorResponse($e, $error);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int     $siteId
	 * @param mixed[] $data
	 *
	 * @return DataResponse
	 */
	public function updatePersonalWebsite(int $siteId, array $data): DataResponse
	{
		try {
			$website = $this->websitesService->getWebsiteFromId($siteId);

			$website->assertOwnedBy($this->userId);

			foreach ($data as $key => $value) {
				switch ($key) {
					case 'type':
						$website->setType((int) $value);
						break;

					case 'theme':
						$website->setTheme($value);
						break;

					default:
						throw new WebsiteInvalidDataException();
				}
			}

			$this->websitesService->updateWebsite($website);

			return $this->getPersonalWebsites();
		} catch (\Exception $e) {
			$error = [];
			if (($e instanceof WebsiteNotFoundException) || ($e instanceof WebsiteForeignOwnerException)) {
				$error['error'] = [ 'field' => 'identifier', 'message' => $this->l10n->t('Website not found.') ];
			} elseif ($e instanceof WebsiteInvalidDataException) {
				$error['error'] = [ 'field' => $e->getField(), 'message' => $e->getMessage() ];
			} elseif ($e instanceof ThemeNotFoundException) {
				$error['error'] = [ 'field' => 'theme', 'message' => $this->l10n->t('Theme not found.') ];
			} elseif ($e instanceof ThemeNotCompatibleException) {
				$error['error'] = [ 'field' => 'theme', 'message' => $this->l10n->t($e->getReason()) ];
			} elseif ($e instanceof TemplateNotFoundException) {
				$error['error'] = [ 'field' => 'template', 'message' => $this->l10n->t('Template not found.') ];
			} elseif ($e instanceof TemplateNotCompatibleException) {
				$error['error'] = [ 'field' => 'template', 'message' => $this->l10n->t($e->getReason()) ];
			}

			return $this->createErrorResponse($e, $error);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $siteId
	 *
	 * @return DataResponse
	 */
	public function removePersonalWebsite(int $siteId): DataResponse
	{
		try {
			$website = $this->websitesService->getWebsiteFromId($siteId);

			$website->assertOwnedBy($this->userId);

			$this->websitesService->deleteWebsite($website);

			return $this->getPersonalWebsites();
		} catch (WebsiteNotFoundException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Website not found.') ]);
		} catch (WebsiteForeignOwnerException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Website not found.') ]);
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}
}
