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

namespace OCA\CMSPico\Tests\Integration\Stage2;

use OCA\CMSPico\Controller\PicoController;
use OCA\CMSPico\Controller\ThemesController;
use OCA\CMSPico\Http\PicoAssetResponse;
use OCA\CMSPico\Http\PicoPageResponse;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Tests\TestCase;
use OCA\CMSPico\Tests\Utils\Manager\TestFilesManager;
use OCA\CMSPico\Tests\Utils\Manager\TestUsersManager;
use OCA\CMSPico\Tests\Utils\Manager\TestWebsitesManager;
use OCP\AppFramework\Http;

/**
 * @group IntegrationStage2
 */
class PicoIntegrationTest extends TestCase
{
	/** @var PicoController */
	protected $controller;

	/** @var TestUsersManager */
	protected $testUsersManager;

	/** @var TestFilesManager */
	protected $testFilesManager;

	/** @var TestWebsitesManager */
	protected $testWebsitesManager;

	/** @var Website */
	protected $website;

	/** @var bool */
	protected static $websiteSetUp = false;

	protected function setUp(): void
	{
		parent::setUp();

		// setup test managers
		$this->testUsersManager = TestUsersManager::getInstance(static::class);
		$this->testFilesManager = TestFilesManager::getInstance(static::class);
		$this->testWebsitesManager = TestWebsitesManager::getInstance(static::class);

		// setup test website
		if (!self::$websiteSetUp) {
			$userId = $this->testUsersManager->getTestUser('user')->getUID();
			$themeName = $this->testFilesManager->copyAppData('themes/test');

			$themePublicPath = TestFilesManager::getPublicAppDataPath('themes/' . $themeName);
			$this->testFilesManager->addPath('appdata_public/themes/test', $themePublicPath);

			/** @var ThemesController $themesController */
			$themesController = \OC::$server->query(ThemesController::class);
			$themesController->addCustomTheme($themeName);

			$path = $this->testFilesManager->copyUserData($userId, 'templates/test', 'files/Test-Website');

			$this->testWebsitesManager->createTestWebsite('test', [
				'user_id' => $userId,
				'path' => $path,
				'theme' => $themeName,
				'template' => 'empty',
			]);

			self::$websiteSetUp = true;
		}

		// setup controller
		$this->controller = \OC::$server->query(PicoController::class);
	}

	public function testGetIndex(): void
	{
		$this->testUsersManager->loginTestUser('viewer');
		$website = $this->testWebsitesManager->getTestWebsite('test');
		$response = $this->controller->getPage($website->getSite(), '');

		$this->assertPicoPageResponse($response, 'output/PicoIntegrationTest/testGetIndex.txt');
	}

	public function testGetSubPage(): void
	{
		$this->testUsersManager->loginTestUser('viewer');
		$website = $this->testWebsitesManager->getTestWebsite('test');
		$response = $this->controller->getPage($website->getSite(), 'sub/page');

		$this->assertPicoPageResponse($response, 'output/PicoIntegrationTest/testGetSubPage.txt');
	}

	public function testGetImage(): void
	{
		$this->testUsersManager->loginTestUser('viewer');
		$website = $this->testWebsitesManager->getTestWebsite('test');
		$response = $this->controller->getAsset($website->getSite(), 'image.png');

		$this->assertPicoAssetResponse($response, 'image/png', 'templates/test/assets/image.png');
	}

	protected function assertPicoPageResponse($response, string $expectedOutputFile = null): void
	{
		/** @var PicoPageResponse $response */
		$this->assertInstanceOf(PicoPageResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

		if ($expectedOutputFile !== null) {
			$this->assertStringEqualsFile(
				TestFilesManager::getSourcePath($expectedOutputFile),
				$response->render()
			);
		}
	}

	protected function assertPicoAssetResponse(
		$response,
		string $expectedContentType,
		string $expectedOutputFile = null
	): void {
		/** @var PicoAssetResponse $response */
		$this->assertInstanceOf(PicoAssetResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

		$actualContentType = $response->getHeaders()['Content-Type'] ?? null;
		$this->assertSame($expectedContentType, $actualContentType);

		if ($expectedOutputFile !== null) {
			$this->assertStringEqualsFile(
				TestFilesManager::getSourcePath($expectedOutputFile),
				$response->render()
			);
		}
	}
}
