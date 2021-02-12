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

use OCA\CMSPico\Controller\ThemesController;
use OCA\CMSPico\Model\Theme;
use OCA\CMSPico\Tests\TestCase;
use OCA\CMSPico\Tests\Utils\Manager\TestExtensionManager;
use OCA\CMSPico\Tests\Utils\Manager\TestFilesManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

/**
 * @group IntegrationStage1
 */
class ThemesControllerIntegrationTest extends TestCase
{
	/** @var ThemesController */
	protected $controller;

	/** @var TestFilesManager */
	protected $testFilesManager;

	/** @var TestExtensionManager */
	protected $testThemesManager;

	protected function setUp(): void
	{
		parent::setUp();

		// setup test files
		$this->testFilesManager = TestFilesManager::getInstance(static::class);

		// setup test themes
		$this->testThemesManager = TestExtensionManager::getInstance(static::class);

		// setup controller
		$this->controller = \OC::$server->query(ThemesController::class);
	}

	public function testGetInitialThemes(): void
	{
		$response = $this->controller->getThemes();
		$data = $this->assertValidResponse($response);

		$this->testThemesManager
			->assertValidItems($data)
			->initItems($data);
	}

	/**
	 * @depends testGetInitialThemes
	 */
	public function testGetNewTheme(): string
	{
		$themeName = $this->testFilesManager->copyAppData('themes/test');

		$response = $this->controller->getThemes();
		$data = $this->assertValidResponse($response);

		$this->testThemesManager
			->addNewItem($themeName)
			->assertMatchingItems($data);

		return $themeName;
	}

	/**
	 * @depends testGetNewTheme
	 *
	 * @param string $themeName
	 */
	public function testAddTheme(string $themeName): string
	{
		$themeData = [ 'name' => $themeName, 'type' => Theme::TYPE_CUSTOM, 'compat' => true ];

		$themePublicPath = TestFilesManager::getPublicAppDataPath('themes/' . $themeName);
		$this->testFilesManager->addPath('appdata_public/themes/test', $themePublicPath);

		$response = $this->controller->addCustomTheme($themeName);
		$data = $this->assertValidResponse($response);

		$this->testThemesManager
			->removeNewItem($themeName)
			->addCustomItem($themeName, $themeData)
			->assertMatchingItems($data);

		$this->assertFilesExist($data);

		return $themeName;
	}

	/**
	 * @depends testGetInitialThemes
	 */
	public function testCopyTheme(): string
	{
		$themeName = $this->getThemeName('other');
		$themeData = [ 'name' => $themeName, 'type' => Theme::TYPE_CUSTOM, 'compat' => true ];

		$this->testFilesManager
			->addPath('appdata_public/themes/other', TestFilesManager::getPublicAppDataPath('themes/' . $themeName))
			->addPath('themes/other', TestFilesManager::getAppDataPath('themes/' . $themeName));

		$response = $this->controller->copyTheme('default', $themeName);
		$data = $this->assertValidResponse($response);

		$this->testThemesManager
			->addCustomItem($themeName, $themeData)
			->assertMatchingItems($data);

		$this->assertFilesExist($data);

		return $themeName;
	}

	/**
	 * @depends testAddTheme
	 *
	 * @param string $themeName
	 */
	public function testUpdateTheme(string $themeName): void
	{
		$response = $this->controller->updateCustomTheme($themeName);
		$data = $this->assertValidResponse($response);

		$this->testThemesManager
			->assertMatchingItems($data);

		$this->assertFilesExist($data);
	}

	/**
	 * @depends testCopyTheme
	 *
	 * @param string $themeName
	 */
	public function testRemoveTheme(string $themeName): void
	{
		$response = $this->controller->removeCustomTheme($themeName);
		$data = $this->assertValidResponse($response);

		$this->testThemesManager
			->removeCustomItem($themeName)
			->addNewItem($themeName)
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
		foreach ($data['systemItems'] ?? [] as $themeName => $_) {
			$this->assertDirectoryExists(TestFilesManager::getPublicAppDataPath('themes/' . $themeName));
		}

		foreach ($data['customItems'] ?? [] as $themeName => $_) {
			$this->assertDirectoryExists(TestFilesManager::getAppDataPath('themes/' . $themeName));
			$this->assertDirectoryExists(TestFilesManager::getPublicAppDataPath('themes/' . $themeName));
		}

		foreach ($data['newItems'] ?? [] as $themeName) {
			$this->assertDirectoryExists(TestFilesManager::getAppDataPath('themes/' . $themeName));
			$this->assertDirectoryNotExists(TestFilesManager::getPublicAppDataPath('themes/' . $themeName));
		}
	}

	private function getThemeName(string $name): string
	{
		return $name . '_' . substr(hash('sha256', static::class), 0, 16);
	}
}
