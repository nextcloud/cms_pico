<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2020, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
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

use OCA\CMSPico\Controller\PicoController;
use OCA\CMSPico\Http\NotPermittedResponse;
use OCA\CMSPico\Http\PicoAssetResponse;
use OCA\CMSPico\Http\PicoPageResponse;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Tests\TestCase;
use OCA\CMSPico\Tests\Utils\Manager\TestUsersManager;
use OCA\CMSPico\Tests\Utils\Manager\TestWebsitesManager;
use OCP\AppFramework\Http;

/**
 * @group IntegrationStage1
 */
class PicoControllerIntegrationTest extends TestCase
{
	/** @var PicoController */
	protected $controller;

	/** @var TestUsersManager */
	protected $testUsersManager;

	/** @var TestWebsitesManager */
	protected $testWebsitesManager;

	/** @var Website */
	protected $website;

	protected function setUp(): void
	{
		parent::setUp();

		// setup test users
		$this->testUsersManager = TestUsersManager::getInstance(static::class);

		// setup test websites
		$this->testWebsitesManager = TestWebsitesManager::getInstance(static::class);

		$ownerUserId = $this->testUsersManager->getTestUser('owner')->getUID();

		$this->testWebsitesManager->createTestWebsite('public', [
			'user_id' => $ownerUserId,
		]);

		$this->testWebsitesManager->createTestWebsite('private', [
			'user_id' => $ownerUserId,
			'type' => Website::TYPE_PRIVATE,
		]);

		// setup controller
		$this->controller = \OC::$server->query(PicoController::class);
	}

	public function testGetPublicPage(): void
	{
		$this->testUsersManager->loginTestUser('viewer');
		$website = $this->testWebsitesManager->getTestWebsite('public');
		$response = $this->controller->getPage($website->getSite(), '');

		$this->assertPicoPageResponse($response);
	}

	public function testGetPublicPageAsGuest(): void
	{
		$this->testUsersManager->logoutUser();
		$website = $this->testWebsitesManager->getTestWebsite('public');
		$response = $this->controller->getPage($website->getSite(), '');

		$this->assertPicoPageResponse($response);
	}

	public function testGetPublicAsset(): void
	{
		$this->testUsersManager->loginTestUser('viewer');
		$website = $this->testWebsitesManager->getTestWebsite('public');
		$response = $this->controller->getAsset($website->getSite(), 'image.png');

		$this->assertPicoAssetResponse($response, 'image/png');
	}

	public function testGetPublicAssetAsGuest(): void
	{
		$this->testUsersManager->logoutUser();
		$website = $this->testWebsitesManager->getTestWebsite('public');
		$response = $this->controller->getAsset($website->getSite(), 'image.png');

		$this->assertPicoAssetResponse($response, 'image/png');
	}

	public function testGetOwnPrivatePage(): void
	{
		$this->testUsersManager->loginTestUser('owner');
		$website = $this->testWebsitesManager->getTestWebsite('private');
		$response = $this->controller->getPage($website->getSite(), '');

		$this->assertPicoPageResponse($response);
	}

	public function testGetForeignPrivatePage(): void
	{
		$this->testUsersManager->loginTestUser('viewer');
		$website = $this->testWebsitesManager->getTestWebsite('private');
		$response = $this->controller->getPage($website->getSite(), '');

		$this->assertErrorResponse($response, Http::STATUS_FORBIDDEN);
	}

	protected function assertPicoPageResponse($response): void
	{
		$this->assertInstanceOf(PicoPageResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

		/** @var PicoPageResponse $response */
		$html = $response->render();
		$htmlFirstLine = strstr($html, "\n", true);
		$this->assertSame('<!DOCTYPE html>', $htmlFirstLine);
	}

	protected function assertPicoAssetResponse($response, string $expectedContentType): void
	{
		/** @var PicoAssetResponse $response */
		$this->assertInstanceOf(PicoAssetResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

		$actualContentType = $response->getHeaders()['Content-Type'] ?? null;
		$this->assertSame($expectedContentType, $actualContentType);
	}

	protected function assertErrorResponse($response, int $expectedStatusCode, string $expectedMessage = null): void
	{
		/** @var NotPermittedResponse $response */
		$this->assertInstanceOf(NotPermittedResponse::class, $response);
		$this->assertSame($expectedStatusCode, $response->getStatus());

		if ($expectedMessage !== null) {
			$actualMessage = $response->getParams()['error'] ?? null;
			$this->assertSame($expectedMessage, $actualMessage);
		}
	}
}
