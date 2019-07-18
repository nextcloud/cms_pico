<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2017, Maxence Lange (<maxence@artificial-owl.com>)
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

namespace OCA\CMSPico\Tests\Service;

use Exception;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\WebsiteForeignOwnerException;
use OCA\CMSPico\Exceptions\WebsiteExistsException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Service\WebsitesService;
use OCA\CMSPico\Tests\Env;


class WebsitesServiceTest extends \PHPUnit_Framework_TestCase {

	const INFOS_WEBSITE1 = [
		'name'     => 'website1',
		'path'     => '/website1',
		'type'     => '1',
		'site'     => 'website1',
		'template' => 'sample_pico',
		'private'  => '0'
	];

	const INFOS_WEBSITE2 = [
		'name'     => 'website2',
		'path'     => '/website2',
		'type'     => '1',
		'site'     => 'website2',
		'template' => 'sample_pico',
		'private'  => '0'
	];


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

		try {
			$this->createWebsite($data);
			$this->assertSame(true, false, 'should return an exception');
		} catch (WebsiteExistsException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should return WebsiteAlreadyExistException');
		}

	}


	/**
	 *
	 */
	public function testWebsite2Creation() {
		$data = self::INFOS_WEBSITE2;
		$data['user_id'] = Env::ENV_TEST_USER2;

		try {
			$this->createWebsite($data);
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should not returns Exception - ' . $e->getMessage());
		}
	}


	/**
	 *
	 */
	public function testWebsitesListing() {
		$this->assertCount(1, $this->websitesService->getWebsitesFromUser(Env::ENV_TEST_USER2));
		$this->assertCount(0, $this->websitesService->getWebsitesFromUser(Env::ENV_TEST_USER3));

		$websites = $this->websitesService->getWebsitesFromUser(Env::ENV_TEST_USER1);
		$this->assertCount(1, $websites);

		$arr = json_decode(json_encode($websites[0]), true);
		$path = rtrim(self::INFOS_WEBSITE1['path'], '/') . '/';
		$path2 = rtrim($arr['path'], '/') . '/';

		$this->assertSame(
			[
				'name'    => self::INFOS_WEBSITE1['name'],
				'user_id' => Env::ENV_TEST_USER1,
				'path'    => $path,
				'site'    => self::INFOS_WEBSITE1['site'],
				'type'    => self::INFOS_WEBSITE1['type']
			],
			[
				'name'    => $arr['name'],
				'user_id' => $arr['user_id'],
				'path'    => $path2,
				'site'    => $arr['site'],
				'type'    => $arr['type']
			]
		);

	}


	public function testWebsiteUpdate() {
		$websites = $this->websitesService->getWebsitesFromUser(Env::ENV_TEST_USER1);
		$this->assertCount(1, $websites);

		$website = array_shift($websites);
		$websiteCopyData = json_encode($website);
		$website->setName('name2');
		$website->setTheme('theme2');
		$website->setSite('site2');
		$website->setOption('private', '1');

		$this->websitesService->updateWebsite($website);

		$websites = $this->websitesService->getWebsitesFromUser(Env::ENV_TEST_USER1);
		$this->assertCount(1, $websites);
		$website2 = array_shift($websites);

		$this->assertSame(
			[
				'name'    => 'name2',
				'theme'   => 'theme2',
				'site'    => 'site2',
				'private' => '1'
			],
			[
				'name'    => $website2->getName(),
				'theme'   => $website2->getTheme(),
				'site'    => $website2->getSite(),
				'private' => $website2->getOption('private')
			]
		);

		$websiteCopy = new Website(json_decode($websiteCopyData, true));
		$this->websitesService->updateWebsite($websiteCopy);
	}


	public function testWebpage() {

		$websites = $this->websitesService->getWebsitesFromUser(Env::ENV_TEST_USER1);
		$website = array_shift($websites);

		// test normal website.
		$content = $this->websitesService->getPage(
			$website->getSite(), '', Env::ENV_TEST_USER1
		)->getContent();

		if (substr($content, 0, 15) !== '<!DOCTYPE html>') {
			$this->assertSame(true, false, 'Unexpected content');
		}

		// test random website
		try {
			$this->websitesService->getPage(
				'random_website', '', Env::ENV_TEST_USER1
			);
			$this->assertSame(true, false, 'Should return an exception');
		} catch (WebsiteNotFoundException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'Should return WebsiteDoesNotExistException');
		}

		// test website with no content
		rename($website->getWebsitePath() . 'content', './content');
		try {
			$content =
				$this->websitesService->getPage($website->getSite(), '', Env::ENV_TEST_USER1)
					->getContent();
			$this->assertSame(true, false, 'Should return an exception');
		} catch (PicoRuntimeException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'Should return PicoRuntimeException' . $e->getMessage());
		}

		rename('./content', $website->getAbsolutePath() . 'content');


	}


	/**
	 *
	 */
	public function testWebsiteDeletion() {
		$data = self::INFOS_WEBSITE1;


		try {
			$data['user_id'] = Env::ENV_TEST_USER2;
			$this->deleteWebsite($data);
		} catch (WebsiteForeignOwnerException $e) {
		} catch (Exception $e) {
			$this->assertSame(
				true, false, 'should returns UserIsNotOwnerException - ' . $e->getMessage()
			);
		}


		$data['user_id'] = Env::ENV_TEST_USER1;

		try {
			$this->deleteWebsite($data);
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should not returns Exception - ' . $e->getMessage());
		}

		try {
			$this->deleteWebsite($data);
			$this->assertSame(true, false, 'should return an exception');
		} catch (WebsiteNotFoundException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should return WebsiteDoesNotExistException');
		}

	}


	public function testUserRemoved() {
		$websites = $this->websitesService->getWebsitesFromUser(Env::ENV_TEST_USER2);
		$this->assertCount(1, $websites);

		$this->websitesService->onUserRemoved(Env::ENV_TEST_USER2);

		$websites = $this->websitesService->getWebsitesFromUser(Env::ENV_TEST_USER2);
		$this->assertCount(0, $websites);
	}


	/**
	 * @param array $data
	 */
	private function createWebsite($data)
	{
		$website = new Website($data);
		$this->websitesService->createWebsite($website);
	}

	/**
	 * @param array $data
	 */
	private function deleteWebsite($data)
	{
		$website = $this->websitesService->getWebsiteFromSite($data['site']);
		$website->assertOwnedBy($data['user_id']);

		$this->websitesService->deleteWebsite($website);
	}

}
