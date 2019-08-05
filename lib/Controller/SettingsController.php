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
use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Exceptions\ThemeNotCompatibleException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteExistsException;
use OCA\CMSPico\Exceptions\WebsiteForeignOwnerException;
use OCA\CMSPico\Exceptions\WebsiteInvalidDataException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\PluginsService;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;

class SettingsController extends Controller
{
	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var ILogger */
	private $logger;

	/** @var ConfigService */
	private $configService;

	/** @var TemplatesService */
	private $templatesService;

	/** @var ThemesService */
	private $themesService;

	/** @var PluginsService */
	private $pluginsService;

	/** @var WebsitesService */
	private $websitesService;

	/**
	 * SettingsController constructor.
	 *
	 * @param IRequest         $request
	 * @param string           $userId
	 * @param IL10N            $l10n
	 * @param ILogger          $logger
	 * @param ConfigService    $configService
	 * @param TemplatesService $templatesService
	 * @param ThemesService    $themesService
	 * @param PluginsService   $pluginsService
	 * @param WebsitesService  $websitesService
	 */
	public function __construct(
		IRequest $request,
		$userId,
		IL10N $l10n,
		ILogger $logger,
		ConfigService $configService,
		TemplatesService $templatesService,
		ThemesService $themesService,
		PluginsService $pluginsService,
		WebsitesService $websitesService
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->configService = $configService;
		$this->templatesService = $templatesService;
		$this->themesService = $themesService;
		$this->pluginsService = $pluginsService;
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
				->setName($data['name'])
				->setUserId($this->userId)
				->setSite($data['site'])
				->setTheme($data['theme'])
				->setPath($data['path'])
				->setTemplateSource($data['template']);

			$this->websitesService->createWebsite($website);

			return $this->getPersonalWebsites();
		} catch (\Exception $e) {
			$data = [];
			if ($e instanceof WebsiteExistsException) {
				$data['form_error'] = [ 'field' => 'site', 'message' => $this->l10n->t('Website exists.') ];
			} elseif (($e instanceof WebsiteInvalidDataException) && $e->getField()) {
				$data['form_error'] = [ 'field' => $e->getField(), 'message' => $e->getMessage() ];
			} elseif ($e instanceof ThemeNotFoundException) {
				$data['form_error'] = [ 'field' => 'theme', 'message' => $this->l10n->t('Theme not found.') ];
			} elseif ($e instanceof ThemeNotCompatibleException) {
				$data['form_error'] = [ 'field' => 'theme', 'message' => $this->l10n->t($e->getReason()) ];
			} elseif ($e instanceof TemplateNotFoundException) {
				$data['form_error'] = [ 'field' => 'template', 'message' => $this->l10n->t('Template not found.') ];
			}

			return $this->createErrorResponse($e, $data);
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
			$data = [];
			if (($e instanceof WebsiteNotFoundException) || ($e instanceof WebsiteForeignOwnerException)) {
				$data['form_error'] = [ 'field' => 'identifier', 'message' => $this->l10n->t('Website not found.') ];
			} elseif ($e instanceof WebsiteInvalidDataException) {
				$data['form_error'] = [ 'field' => $e->getField(), 'message' => $e->getMessage() ];
			} elseif ($e instanceof ThemeNotFoundException) {
				$data['form_error'] = [ 'field' => 'theme', 'message' => $this->l10n->t('Theme not found.') ];
			} elseif ($e instanceof ThemeNotCompatibleException) {
				$data['form_error'] = [ 'field' => 'theme', 'message' => $this->l10n->t($e->getReason()) ];
			} elseif ($e instanceof TemplateNotFoundException) {
				$data['form_error'] = [ 'field' => 'template', 'message' => $this->l10n->t('Template not found.') ];
			}

			return $this->createErrorResponse($e, $data);
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
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @return DataResponse
	 */
	public function getTemplates(): DataResponse
	{
		$data = [
			'systemItems' => $this->templatesService->getSystemTemplates(),
			'customItems' => $this->templatesService->getCustomTemplates(),
			'newItems' => $this->templatesService->getNewCustomTemplates(),
		];

		return new DataResponse($data, Http::STATUS_OK);
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function addCustomTemplate(string $item): DataResponse
	{
		try {
			$customTemplates = $this->templatesService->getCustomTemplates();
			$customTemplates[] = $item;

			$this->configService->setAppValue(ConfigService::CUSTOM_TEMPLATES, json_encode($customTemplates));

			return $this->getTemplates();
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function removeCustomTemplate(string $item): DataResponse
	{
		try {
			$customTemplates = $this->templatesService->getCustomTemplates();

			$newCustomTemplates = [];
			foreach ($customTemplates as $customTemplate) {
				if ($customTemplate === $item) {
					continue;
				}

				$newCustomTemplates[] = $customTemplate;
			}

			$this->configService->setAppValue(ConfigService::CUSTOM_TEMPLATES, json_encode($newCustomTemplates));

			return $this->getTemplates();
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @return DataResponse
	 */
	public function getThemes(): DataResponse
	{
		$data = [
			'systemItems' => $this->themesService->getSystemThemes(),
			'customItems' => $this->themesService->getCustomThemes(),
			'newItems' => $this->themesService->getNewCustomThemes(),
		];

		return new DataResponse($data, Http::STATUS_OK);
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function addCustomTheme(string $item): DataResponse
	{
		try {
			$this->themesService->publishCustomTheme($item);

			return $this->getThemes();
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function updateCustomTheme(string $item): DataResponse
	{
		try {
			$this->themesService->depublishCustomTheme($item);
			$this->themesService->publishCustomTheme($item);

			return $this->getThemes();
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function removeCustomTheme(string $item): DataResponse
	{
		try {
			$this->themesService->depublishCustomTheme($item);

			return $this->getThemes();
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @return DataResponse
	 */
	public function getPlugins(): DataResponse
	{
		$data = [
			'systemItems' => $this->pluginsService->getSystemPlugins(),
			'customItems' => $this->pluginsService->getCustomPlugins(),
			'newItems' => $this->pluginsService->getNewCustomPlugins(),
		];

		return new DataResponse($data, Http::STATUS_OK);
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function addCustomPlugin(string $item): DataResponse
	{
		try {
			$this->pluginsService->publishCustomPlugin($item);

			return $this->getPlugins();
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function updateCustomPlugin(string $item): DataResponse
	{
		try {
			$this->pluginsService->depublishCustomPlugin($item);
			$this->pluginsService->publishCustomPlugin($item);

			return $this->getPlugins();
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param string $item
	 *
	 * @return DataResponse
	 */
	public function removeCustomPlugin(string $item): DataResponse
	{
		try {
			$this->pluginsService->depublishCustomPlugin($item);

			return $this->getPlugins();
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param array $data
	 *
	 * @return DataResponse
	 */
	public function setLinkMode(array $data): DataResponse
	{
		try {
			if (!isset($data['link_mode'])) {
				throw new \UnexpectedValueException();
			}

			$this->websitesService->setLinkMode((int) $data['link_mode']);

			return new DataResponse([], Http::STATUS_CREATED);
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param \Exception $exception
	 * @param array      $data
	 *
	 * @return DataResponse
	 */
	private function createErrorResponse(\Exception $exception, array $data = []): DataResponse
	{
		$this->logger->logException($exception, [ 'app' => Application::APP_NAME, 'level' => 2 ]);

		$data['status'] = 0;
		if (\OC::$server->getSystemConfig()->getValue('debug', false)) {
			$data['exception'] = get_class($exception);
			$data['error'] = $exception->getMessage();
			$data['code'] = $exception->getCode();
		}

		return new DataResponse($data, Http::STATUS_INTERNAL_SERVER_ERROR);
	}
}
