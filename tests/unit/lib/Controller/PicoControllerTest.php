<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\CMSPico\Tests\Service;

use Exception;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Controller\PicoController;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Service\WebsitesService;
use OCA\CMSPico\Tests\Env;


class PicoControllerTest extends \PHPUnit_Framework_TestCase {

	const INFOS_WEBSITE1 = [
		'name'     => 'pico 1',
		'path'     => '/pico1',
		'type'     => '1',
		'site'     => 'pico',
		'template' => 0,
		'private'  => '0'
	];


	/** @var PicoController */
	private $picoController;

	/** @var WebsitesService */
	private $websitesService;

	/**
	 * setUp() is initiated before each test.
	 *
	 * @throws Exception
	 */
	protected function setUp() {
		Env::setUser(Env::ENV_TEST_USER1);
		Env::logout();

		$app = new Application();
		$container = $app->getContainer();

		$this->picoController = $container->query(PicoController::class);
		$this->websitesService = $container->query(WebsitesService::class);
	}


	/**
	 * tearDown() is initiated after each test.
	 *
	 * @throws Exception
	 */
	protected function tearDown() {
		Env::setUser(Env::ENV_TEST_USER1);
		Env::logout();
	}


	/**
	 *
	 */
	public function testWebsiteCreation() {
		$data = self::INFOS_WEBSITE1;
		$data['user_id'] = Env::ENV_TEST_USER1;

		try {
			$this->createWebsite($data);
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should not returns Exception - ' . $e->getMessage());
		}
	}


	/**
	 *
	 */
	public function testGetPage() {
		$result = $this->picoController->getPage(self::INFOS_WEBSITE1['site']);
		$content = $result->getData();
		if (substr($content, 0, 15) !== '<!DOCTYPE html>') {
			$this->assertSame(true, false, 'Unexpected content');
		}
	}


	/**
	 *
	 */
	public function testGetRoot() {
		$result = $this->picoController->getRoot(self::INFOS_WEBSITE1['site']);
		$content = $result->getData();
		if (substr($content, 0, 15) !== '<!DOCTYPE html>') {
			$this->assertSame(true, false, 'Unexpected content');
		}

		try {

			$this->picoController->getRoot('random_website');
			$this->assertSame(true, false, 'Should return Exception');
		} catch (Exception $e) {
		}

	}


	public function testWebsiteDeletion() {
		$data = self::INFOS_WEBSITE1;
		$data['user_id'] = Env::ENV_TEST_USER1;

		try {
			$this->deleteWebsite($data);
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should not returns Exception - ' . $e->getMessage());
		}
	}

	/**
	 * @param array $data
	 */
	private function createWebsite($data) {
		$website = new Website($data);

		$this->websitesService->createWebsite(
			$website->getName(), $website->getUserId(), $website->getSite(), $website->getPath(), 0
		);
	}

	/**
	 * @param array $data
	 */
	private function deleteWebsite($data) {
		$website = $this->websitesService->getWebsiteFromSite($data['site']);

		$this->websitesService->deleteWebsite($website->getId(), $data['user_id']);
	}


}