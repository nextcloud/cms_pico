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
use OCA\CMSPico\Exceptions\TemplateAlreadyExistsException;
use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Service\TemplatesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class TemplatesController extends Controller
{
	use ControllerTrait;

	/** @var IL10N */
	private $l10n;

	/** @var TemplatesService */
	private $templatesService;

	/**
	 * TemplatesController constructor.
	 *
	 * @param IRequest         $request
	 * @param IL10N            $l10n
	 * @param LoggerInterface  $logger
	 * @param TemplatesService $templatesService
	 */
	public function __construct(IRequest $request, IL10N $l10n, LoggerInterface $logger, TemplatesService $templatesService)
	{
		parent::__construct(Application::APP_NAME, $request);

		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->templatesService = $templatesService;
	}

	/**
	 * @return DataResponse
	 */
	public function getTemplates(): DataResponse
	{
		try {
			$data = [
				'systemItems' => $this->templatesService->getSystemTemplates(),
				'customItems' => $this->templatesService->getCustomTemplates(),
				'newItems' => $this->templatesService->getNewCustomTemplates(),
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
	public function addCustomTemplate(string $item): DataResponse
	{
		try {
			$this->templatesService->registerCustomTemplate($item);

			return $this->getTemplates();
		} catch (TemplateNotFoundException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Template not found.') ]);
		} catch (TemplateAlreadyExistsException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Template exists already.') ]);
		} catch (\Throwable $e) {
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
			$this->templatesService->removeCustomTemplate($item);

			return $this->getTemplates();
		} catch (TemplateNotFoundException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Template not found.') ]);
		} catch (\Throwable $e) {
			return $this->createErrorResponse($e);
		}
	}

	/**
	 * @param string $item
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function copyTemplate(string $item, string $name): DataResponse
	{
		try {
			$this->templatesService->copyTemplate($item, $name);

			return $this->getTemplates();
		} catch (TemplateNotFoundException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Template not found.') ]);
		} catch (TemplateAlreadyExistsException $e) {
			return $this->createErrorResponse($e, [ 'error' => $this->l10n->t('Template exists already.') ]);
		} catch (\Throwable $e) {
			return $this->createErrorResponse($e);
		}
	}
}
