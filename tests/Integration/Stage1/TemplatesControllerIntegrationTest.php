<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2021, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
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

namespace OCA\CMSPico\Tests\Integration\Stage1;

use OCA\CMSPico\Controller\TemplatesController;
use OCA\CMSPico\Model\Template;
use OCA\CMSPico\Tests\TestCase;
use OCA\CMSPico\Tests\Utils\Manager\TestExtensionManager;
use OCA\CMSPico\Tests\Utils\Manager\TestFilesManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

/**
 * @group IntegrationStage1
 */
class TemplatesControllerIntegrationTest extends TestCase
{
	/** @var TemplatesController */
	protected $controller;

	/** @var TestFilesManager */
	protected $testFilesManager;

	/** @var TestExtensionManager */
	protected $testTemplatesManager;

	protected function setUp(): void
	{
		parent::setUp();

		// setup test files
		$this->testFilesManager = TestFilesManager::getInstance(static::class);

		// setup test templates
		$this->testTemplatesManager = TestExtensionManager::getInstance(static::class);

		// setup controller
		$this->controller = \OC::$server->query(TemplatesController::class);
	}

	public function testGetInitialTemplates(): void
	{
		$response = $this->controller->getTemplates();
		$data = $this->assertValidResponse($response);

		$this->testTemplatesManager
			->assertValidItems($data)
			->initItems($data);
	}

	/**
	 * @depends testGetInitialTemplates
	 */
	public function testGetNewTemplate(): string
	{
		$templateName = $this->testFilesManager->copyAppData('templates/test');

		$response = $this->controller->getTemplates();
		$data = $this->assertValidResponse($response);

		$this->testTemplatesManager
			->addNewItem($templateName)
			->assertMatchingItems($data);

		return $templateName;
	}

	/**
	 * @depends testGetNewTemplate
	 *
	 * @param string $templateName
	 */
	public function testAddTemplate(string $templateName): string
	{
		$templateData = [ 'name' => $templateName, 'type' => Template::TYPE_CUSTOM, 'compat' => true ];

		$response = $this->controller->addCustomTemplate($templateName);
		$data = $this->assertValidResponse($response);

		$this->testTemplatesManager
			->removeNewItem($templateName)
			->addCustomItem($templateName, $templateData)
			->assertMatchingItems($data);

		$this->assertFilesExist($data);

		return $templateName;
	}

	/**
	 * @depends testGetInitialTemplates
	 */
	public function testCopyTemplate(): string
	{
		$templateName = $this->getTemplateName('other');
		$templateData = [ 'name' => $templateName, 'type' => Template::TYPE_CUSTOM, 'compat' => true ];

		$response = $this->controller->copyTemplate('empty', $templateName);
		$data = $this->assertValidResponse($response);

		$this->testFilesManager->addPath('templates/' . $templateName);

		$this->testTemplatesManager
			->addCustomItem($templateName, $templateData)
			->assertMatchingItems($data);

		$this->assertFilesExist($data);

		return $templateName;
	}

	/**
	 * @depends testCopyTemplate
	 *
	 * @param string $templateName
	 */
	public function testRemoveTemplate(string $templateName): void
	{
		$response = $this->controller->removeCustomTemplate($templateName);
		$data = $this->assertValidResponse($response);

		$this->testTemplatesManager
			->removeCustomItem($templateName)
			->addNewItem($templateName)
			->assertMatchingItems($data);

		$this->assertFilesExist($data);
	}

	protected function assertValidResponse($response): array
	{
		/** @var DataResponse $response */
		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

		$this->assertIsArray($response->getData());
		return $response->getData();
	}

	protected function assertFilesExist(array $data): void
	{
		foreach ($data['customItems'] ?? [] as $templateName => $_) {
			$this->assertDirectoryExists(TestFilesManager::getAppDataPath('templates/' . $templateName));
		}

		foreach ($data['newItems'] ?? [] as $templateName) {
			$this->assertDirectoryExists(TestFilesManager::getAppDataPath('templates/' . $templateName));
		}
	}

	private function getTemplateName(string $name): string
	{
		return $name . '_' . substr(hash('sha256', static::class), 0, 16);
	}
}
