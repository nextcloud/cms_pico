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

use OCA\CMSPico\Controller\WebsitesController;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Model\WebsiteCore;
use OCA\CMSPico\Tests\TestCase;
use OCA\CMSPico\Tests\Utils\Manager\TestUsersManager;
use OCA\CMSPico\Tests\Utils\Manager\TestWebsitesManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\Constraint\LogicalAnd;

/**
 * @group IntegrationStage1
 */
class WebsitesControllerIntegrationTest extends TestCase
{
	/** @var WebsitesController */
	protected $controller;

	/** @var TestUsersManager */
	protected $testUsersManager;

	/** @var TestWebsitesManager */
	protected $testWebsitesManager;

	protected function setUp(): void
	{
		parent::setUp();

		// setup test users
		$this->testUsersManager = TestUsersManager::getInstance(static::class);
		$this->testUsersManager->loginTestUser('user');

		// setup test websites
		$this->testWebsitesManager = TestWebsitesManager::getInstance(static::class);

		// setup controller
		$this->controller = \OC::$server->query(WebsitesController::class);
	}

	public function testGetEmptyPersonalWebsites(): void
	{
		$response = $this->controller->getPersonalWebsites();
		$this->assertWebsitesResponse($response, []);
	}

	public function testCreatePersonalWebsite(): void
	{
		$data = $this->testWebsitesManager->createTestWebsiteData('test1');

		$response = $this->controller->createPersonalWebsite([
			'site' => $data['site'],
			'name' => $data['name'],
			'path' => $data['path'],
			'theme' => $data['theme'],
			'template' => $data['template'],
		]);

		$this->assertWebsitesResponse($response, [
			$data
		]);

		/** @var Website $website */
		$website = $response->getData()['websites'][0];
		$this->testWebsitesManager->addTestWebsite('test1', $website);
	}

	/**
	 * @depends testCreatePersonalWebsite
	 */
	public function testCreateDuplicatePersonalWebsite(): void
	{
		$data = $this->testWebsitesManager->createTestWebsiteData('test1');

		$response = $this->controller->createPersonalWebsite([
			'site' => $data['site'],
			'name' => $data['name'],
			'path' => $data['path'],
			'theme' => $data['theme'],
			'template' => $data['template'],
		]);

		$this->assertErrorResponse($response, 'site', 'Website exists.');
	}

	/**
	 * @depends testCreatePersonalWebsite
	 */
	public function testCreateAnotherPersonalWebsite(): void
	{
		$data = $this->testWebsitesManager->createTestWebsiteData('test2');

		$response = $this->controller->createPersonalWebsite([
			'site' => $data['site'],
			'name' => $data['name'],
			'path' => $data['path'],
			'theme' => $data['theme'],
			'template' => $data['template'],
		]);

		$website1 = $this->testWebsitesManager->getTestWebsite('test1');
		$this->assertWebsitesResponse($response, [
			$website1->getData(),
			$data
		]);

		/** @var Website $website2 */
		$website2 = $response->getData()['websites'][1];
		$this->testWebsitesManager->addTestWebsite('test2', $website2);
	}

	/**
	 * @depends testCreatePersonalWebsite
	 * @depends testCreateAnotherPersonalWebsite
	 */
	public function testUpdatePersonalWebsite(): void
	{
		$website1 = $this->testWebsitesManager->getTestWebsite('test1');

		$response = $this->controller->updatePersonalWebsite($website1->getId(), [
			'type' => WebsiteCore::TYPE_PRIVATE
		]);

		$this->assertWebsitesResponse($response, [
			array_merge($website1->getData(), [ 'type' => WebsiteCore::TYPE_PRIVATE ]),
			$this->testWebsitesManager->getTestWebsite('test2')->getData(),
		]);

		/** @var Website $website1 */
		$website1 = $response->getData()['websites'][0];
		$this->testWebsitesManager->addTestWebsite('test1', $website1);
	}

	/**
	 * @depends testCreatePersonalWebsite
	 * @depends testCreateAnotherPersonalWebsite
	 */
	public function testGetAllPersonalWebsites(): void
	{
		$website1 = $this->testWebsitesManager->getTestWebsite('test1');
		$website2 = $this->testWebsitesManager->getTestWebsite('test2');

		$response = $this->controller->getPersonalWebsites();
		$this->assertWebsitesResponse($response, [
			$website1->getData(),
			$website2->getData()
		]);
	}

	/**
	 * @depends testCreatePersonalWebsite
	 * @depends testCreateAnotherPersonalWebsite
	 */
	public function testRemovePersonalWebsite(): void
	{
		$website1 = $this->testWebsitesManager->getTestWebsite('test1');
		$website2 = $this->testWebsitesManager->getTestWebsite('test2');

		$response = $this->controller->removePersonalWebsite($website1->getId());
		$this->assertWebsitesResponse($response, [
			$website2->getData()
		]);

		$this->testWebsitesManager->removeTestWebsite('test1');
	}

	/**
	 * @param mixed   $response
	 * @param array[] $expectedWebsites
	 */
	protected function assertWebsitesResponse($response, array $expectedWebsites): void
	{
		/** @var DataResponse $response */
		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

		$expectedWebsiteCount = count($expectedWebsites);
		$this->assertThat($response->getData(), LogicalAnd::fromConstraints(
			$this->isType(IsType::TYPE_ARRAY),
			$this->arrayHasKeyWith('websites', $this->sequentialArray($expectedWebsiteCount, $expectedWebsiteCount))
		));

		foreach ($expectedWebsites as $i => $websiteData) {
			/** @var Website $website */
			$website = $response->getData()['websites'][$i];
			$this->assertInstanceOf(Website::class, $website);

			$this->assertThat($website->getData(), $this->associativeArray([
				'id'       => $this->isType(IsType::TYPE_INT),
				'user_id'  => $websiteData['user_id'],
				'site'     => $websiteData['site'],
				'name'     => $websiteData['name'],
				'type'     => $websiteData['type'],
				'path'     => $websiteData['path'],
				'theme'    => $websiteData['theme'],
				'options'  => $this->isType(IsType::TYPE_ARRAY),
				'creation' => $this->isType(IsType::TYPE_INT),
			]));
		}
	}

	/**
	 * @param mixed       $response
	 * @param string|null $expectedField
	 * @param string|null $expectedMessage
	 */
	protected function assertErrorResponse(
		$response,
		string $expectedField = null,
		string $expectedMessage = null
	): void {
		/** @var DataResponse $response */
		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());

		$this->assertThat($response->getData(), $this->logicalAnd(
			$this->isType(IsType::TYPE_ARRAY),
			$this->arrayHasKeyWith('error', $this->associativeArray([
				'field' => $this->logicalAnd(
					$this->isType(IsType::TYPE_STRING),
					$this->logicalNot($this->isEmpty())
				),
				'message' => $this->logicalAnd(
					$this->isType(IsType::TYPE_STRING),
					$this->logicalNot($this->isEmpty())
				),
			]))
		));

		if ($expectedField !== null) {
			$actualField = $response->getData()['error']['field'];
			$this->assertSame($expectedField, $actualField);
		}

		if ($expectedMessage !== null) {
			$actualMessage = $response->getData()['error']['message'];
			$this->assertSame($expectedMessage, $actualMessage);
		}
	}
}
