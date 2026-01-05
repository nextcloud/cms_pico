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
use OCA\CMSPico\Exceptions\WebsiteAlreadyExistsException;
use OCA\CMSPico\Exceptions\WebsiteForeignOwnerException;
use OCA\CMSPico\Exceptions\WebsiteInvalidDataException;
use OCA\CMSPico\Exceptions\WebsiteInvalidOwnerException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use function OCA\CMSPico\t;

class WebsitesController extends Controller
{
	use ControllerTrait;

	/** @var IUserSession */
	private $userSession;

	/** @var IL10N */
	private $l10n;

	/** @var WebsitesService */
	private $websitesService;

	/**
	 * WebsitesController constructor.
	 *
	 * @param IRequest        $request
	 * @param IUserSession    $userSession
	 * @param IL10N           $l10n
	 * @param LoggerInterface $logger
	 * @param WebsitesService $websitesService
	 */
	public function __construct(
		IRequest $request,
		IUserSession $userSession,
		IL10N $l10n,
		LoggerInterface $logger,
		WebsitesService $websitesService
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userSession = $userSession;
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
		try {
			$userId = $this->userSession->getUser()->getUID();
			$data = [ 'websites' => $this->websitesService->getWebsitesFromUser($userId) ];
			return new DataResponse($data);
		} catch (\Throwable $e) {
			return $this->createErrorResponse($e);
		}
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
			$userId = $this->userSession->getUser()->getUID();

			$website = (new Website())
				->setName($data['name'] ?? '')
				->setUserId($userId)
				->setSite($data['site'] ?? '')
				->setTheme($data['theme'] ?? '')
				->setPath($data['path'] ?? '');

			$website->assertValidOwner();

			$this->websitesService->createWebsite($website, $data['template'] ?? '');

			return $this->getPersonalWebsites();
		} catch (\Throwable $e) {
			$error = [];
			if ($e instanceof WebsiteAlreadyExistsException) {
				$error += [ 'errorField' => 'site', 'error' => $this->l10n->t('Website exists.') ];
			} elseif ($e instanceof WebsiteInvalidOwnerException) {
				$error += [ 'errorField' => 'user', 'error' => $this->l10n->t('No permission.') ];
			} elseif (($e instanceof WebsiteInvalidDataException) && $e->getField()) {
				$errorMessage = $e->getError() ?? t('The value given is invalid.');
				$error += [ 'errorField' => $e->getField(), 'error' => $this->l10n->t($errorMessage) ];
			} elseif ($e instanceof ThemeNotFoundException) {
				$error += [ 'errorField' => 'theme', 'error' => $this->l10n->t('Theme not found.') ];
			} elseif ($e instanceof ThemeNotCompatibleException) {
				$error += [ 'errorField' => 'theme', 'error' => $this->l10n->t($e->getReason()) ];
			} elseif ($e instanceof TemplateNotFoundException) {
				$error += [ 'errorField' => 'template', 'error' => $this->l10n->t('Template not found.') ];
			} elseif ($e instanceof TemplateNotCompatibleException) {
				$error += [ 'errorField' => 'template', 'error' => $this->l10n->t($e->getReason()) ];
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

			$userId = $this->userSession->getUser()->getUID();
			$website->assertOwnedBy($userId);

			foreach ($data as $key => $value) {
				switch ($key) {
					case 'name':
						$website->setName($value);
						break;

					case 'type':
						$website->setType((int) $value);
						break;

					case 'theme':
						$website->setTheme($value);
						break;

					case 'options':
						foreach ($value as $optionKey => $optionValue) {
							switch ($optionKey) {
								case 'group_access':
									$groupAccess = $optionValue ? explode('|', $optionValue) : [];
									$website->setGroupAccess($groupAccess);
									break;

								default:
									throw new WebsiteInvalidDataException(
										$website->getSite(),
										'options.' . $optionKey
									);
							}
						}
						break;

					default:
						throw new WebsiteInvalidDataException($website->getSite(), $key);
				}
			}

			$this->websitesService->updateWebsite($website);

			return $this->getPersonalWebsites();
		} catch (\Throwable $e) {
			$error = [];
			if (($e instanceof WebsiteNotFoundException) || ($e instanceof WebsiteForeignOwnerException)) {
				$error += [ 'errorField' => 'identifier', 'error' => $this->l10n->t('Website not found.') ];
			} elseif (($e instanceof WebsiteInvalidDataException) && $e->getField()) {
				$errorMessage = $e->getError() ?? t('The value given is invalid.');
				$error += [ 'errorField' => $e->getField(), 'error' => $this->l10n->t($errorMessage) ];
			} elseif ($e instanceof ThemeNotFoundException) {
				$error += [ 'errorField' => 'theme', 'error' => $this->l10n->t('Theme not found.') ];
			} elseif ($e instanceof ThemeNotCompatibleException) {
				$error += [ 'errorField' => 'theme', 'error' => $this->l10n->t($e->getReason()) ];
			} elseif ($e instanceof TemplateNotFoundException) {
				$error += [ 'errorField' => 'template', 'error' => $this->l10n->t('Template not found.') ];
			} elseif ($e instanceof TemplateNotCompatibleException) {
				$error += [ 'errorField' => 'template', 'error' => $this->l10n->t($e->getReason()) ];
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

			$userId = $this->userSession->getUser()->getUID();
			$website->assertOwnedBy($userId);

			$this->websitesService->deleteWebsite($website);

			return $this->getPersonalWebsites();
		} catch (WebsiteNotFoundException | WebsiteForeignOwnerException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Website not found.') ]);
		} catch (\Throwable $e) {
			return $this->createErrorResponse($e);
		}
	}
}
