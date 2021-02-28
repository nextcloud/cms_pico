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

use OCA\CMSPico\Controller\PluginsController;
use OCA\CMSPico\Model\Plugin;
use OCA\CMSPico\Tests\TestCase;
use OCA\CMSPico\Tests\Utils\Manager\TestExtensionManager;
use OCA\CMSPico\Tests\Utils\Manager\TestFilesManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\GenericFileException;

/**
 * @group IntegrationStage1
 */
class PluginsControllerIntegrationTest extends TestCase
{
	/** @var PluginsController */
	protected $controller;

	/** @var TestFilesManager */
	protected $testFilesManager;

	/** @var TestExtensionManager */
	protected $testPluginsManager;

	protected function setUp(): void
	{
		parent::setUp();

		// setup test files
		$this->testFilesManager = TestFilesManager::getInstance(static::class);

		// setup test plugins
		$this->testPluginsManager = TestExtensionManager::getInstance(static::class);

		// setup controller
		$this->controller = \OC::$server->query(PluginsController::class);
	}

	public function testGetInitialPlugins(): void
	{
		$response = $this->controller->getPlugins();
		$data = $this->assertValidResponse($response);

		$this->testPluginsManager
			->assertValidItems($data)
			->initItems($data);
	}

	/**
	 * @depends testGetInitialPlugins
	 */
	public function testGetNewPlugin(): string
	{
		$pluginName = $this->testFilesManager->copyAppData('plugins/PicoTest');

		$response = $this->controller->getPlugins();
		$data = $this->assertValidResponse($response);

		$this->testPluginsManager
			->addNewItem($pluginName)
			->assertMatchingItems($data);

		return $pluginName;
	}

	/**
	 * @depends testGetNewPlugin
	 *
	 * @param string $pluginName
	 */
	public function testAddPlugin(string $pluginName): string
	{
		$pluginData = [ 'name' => $pluginName, 'type' => Plugin::TYPE_CUSTOM, 'compat' => true ];

		$pluginPublicPath = TestFilesManager::getPublicAppDataPath('plugins/' . $pluginName);
		$this->testFilesManager->addPath('appdata_public/plugins/PicoTest', $pluginPublicPath);

		$this->createTestPlugin($pluginName);

		$response = $this->controller->addCustomPlugin($pluginName);
		$data = $this->assertValidResponse($response);

		$this->testPluginsManager
			->removeNewItem($pluginName)
			->addCustomItem($pluginName, $pluginData)
			->assertMatchingItems($data);

		$this->assertFilesExist($data);

		return $pluginName;
	}

	/**
	 * @depends testAddPlugin
	 *
	 * @param string $pluginName
	 */
	public function testUpdatePlugin(string $pluginName): void
	{
		$response = $this->controller->updateCustomPlugin($pluginName);
		$data = $this->assertValidResponse($response);

		$this->testPluginsManager
			->assertMatchingItems($data);

		$this->assertFilesExist($data);
	}

	/**
	 * @depends testGetInitialPlugins
	 */
	public function testAddAnotherPlugin(): string
	{
		$pluginName = $this->testFilesManager->copyAppData('plugins/PicoTest', 'plugins/PicoOtherTest');
		$pluginData = [ 'name' => $pluginName, 'type' => Plugin::TYPE_CUSTOM, 'compat' => true ];

		$pluginPublicPath = TestFilesManager::getPublicAppDataPath('plugins/' . $pluginName);
		$this->testFilesManager->addPath('appdata_public/plugins/PicoOtherTest', $pluginPublicPath);

		$this->createTestPlugin($pluginName);

		$response = $this->controller->addCustomPlugin($pluginName);
		$data = $this->assertValidResponse($response);

		$this->testPluginsManager
			->addCustomItem($pluginName, $pluginData)
			->assertMatchingItems($data);

		$this->assertFilesExist($data);

		return $pluginName;
	}

	/**
	 * @depends testAddAnotherPlugin
	 *
	 * @param string $pluginName
	 */
	public function testRemovePlugin(string $pluginName): void
	{
		$response = $this->controller->removeCustomPlugin($pluginName);
		$data = $this->assertValidResponse($response);

		$this->testPluginsManager
			->removeCustomItem($pluginName)
			->addNewItem($pluginName)
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
		foreach ($data['systemItems'] ?? [] as $pluginName => $_) {
			$this->assertDirectoryExists(TestFilesManager::getPublicAppDataPath('plugins/' . $pluginName));
		}

		foreach ($data['customItems'] ?? [] as $pluginName => $_) {
			$this->assertDirectoryExists(TestFilesManager::getAppDataPath('plugins/' . $pluginName));
			$this->assertDirectoryExists(TestFilesManager::getPublicAppDataPath('plugins/' . $pluginName));
		}

		foreach ($data['newItems'] ?? [] as $pluginName) {
			$this->assertDirectoryExists(TestFilesManager::getAppDataPath('plugins/' . $pluginName));
			$this->assertDirectoryNotExists(TestFilesManager::getPublicAppDataPath('plugins/' . $pluginName));
		}
	}

	protected function createTestPlugin(string $pluginName): void
	{
		$fullPath = TestFilesManager::getAppDataPath('plugins/' . $pluginName);
		if (!rename($fullPath . '/PicoTest.php', $fullPath . '/' . $pluginName . '.php')) {
			throw new GenericFileException();
		}

		$content = file_get_contents($fullPath . '/' . $pluginName . '.php');
		$content = preg_replace('/^class PicoTest(?= |$)/m', 'class ' . $pluginName, $content);
		if (file_put_contents($fullPath . '/' . $pluginName . '.php', $content) === false) {
			throw new GenericFileException();
		}
	}
}
