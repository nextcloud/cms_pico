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
use OCA\CMSPico\Exceptions\PluginAlreadyExistsException;
use OCA\CMSPico\Exceptions\PluginNotFoundException;
use OCA\CMSPico\Service\PluginsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class PluginsController extends Controller
{
	use ControllerTrait;

	/** @var IL10N */
	private $l10n;

	/** @var PluginsService */
	private $pluginsService;

	/**
	 * PluginsController constructor.
	 *
	 * @param IRequest        $request
	 * @param IL10N           $l10n
	 * @param LoggerInterface $logger
	 * @param PluginsService  $pluginsService
	 */
	public function __construct(IRequest $request, IL10N $l10n, LoggerInterface $logger, PluginsService $pluginsService)
	{
		parent::__construct(Application::APP_NAME, $request);

		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->pluginsService = $pluginsService;
	}

	/**
	 * @return DataResponse
	 */
	public function getPlugins(): DataResponse
	{
		try {
			$data = [
				'systemItems' => $this->pluginsService->getSystemPlugins(),
				'customItems' => $this->pluginsService->getCustomPlugins(),
				'newItems' => $this->pluginsService->getNewCustomPlugins(),
			];

			return new DataResponse($data);
		} catch (\Throwable $e) {
			return $this->createErrorResponse($e);
		}
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
		} catch (PluginNotFoundException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Plugin not found.') ]);
		} catch (PluginAlreadyExistsException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Plugin exists already.') ]);
		} catch (\Throwable $e) {
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
		} catch (PluginNotFoundException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Plugin not found.') ]);
		} catch (\Throwable $e) {
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
		} catch (PluginNotFoundException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Plugin not found.') ]);
		} catch (\Throwable $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function copyDummyPlugin(string $name): DataResponse
	{
		try {
			$this->pluginsService->copyDummyPlugin($name);

			return $this->getPlugins();
		} catch (PluginNotFoundException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Plugin not found.') ]);
		} catch (PluginAlreadyExistsException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Plugin exists already.') ]);
		} catch (\Exception $e) {
			return $this->createErrorResponse($e);
		}
	}
}
