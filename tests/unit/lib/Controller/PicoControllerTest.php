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

namespace OCA\CMSPico\Tests\Service;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Controller\PicoController;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Model\WebsiteCore;
use OCA\CMSPico\Service\WebsitesService;
use OCA\CMSPico\Tests\Env;
use PHPUnit\Framework\TestCase;

class PicoControllerTest extends TestCase
{
	const INFOS_WEBSITE1 = [
		'name'     => 'pico 1',
		'site'     => 'pico',
		'path'     => '/pico1',
		'theme'    => 'default',
		'template' => 'sample_pico',
		'type'     => WebsiteCore::TYPE_PUBLIC
	];

	/** @var PicoController */
	private $picoController;

	/** @var WebsitesService */
	private $websitesService;

	protected function setUp(): void
	{
		Env::setUser(Env::ENV_TEST_USER1);
		Env::logout();

		$app = new Application();
		$container = $app->getContainer();

		$this->picoController = $container->query(PicoController::class);
		$this->websitesService = $container->query(WebsitesService::class);
	}

	protected function tearDown(): void
	{
		Env::setUser(Env::ENV_TEST_USER1);
		Env::logout();
	}

	public function testWebsiteCreation()
	{
		$data = self::INFOS_WEBSITE1;
		$data['user_id'] = Env::ENV_TEST_USER1;

		try {
			$this->createWebsite($data);
		} catch (\Exception $e) {
			$this->assertSame(true, false, 'should not returns Exception - ' . $e->getMessage());
		}
	}

	public function testGetPage()
	{
		$result = $this->picoController->getPage(self::INFOS_WEBSITE1['site'], '');
		$content = $result->render();
		if (substr($content, 0, 15) !== '<!DOCTYPE html>') {
			$this->assertSame(true, false, 'Unexpected content');
		}
	}

	public function testGetRoot()
	{
		$result = $this->picoController->getRoot(self::INFOS_WEBSITE1['site']);
		$content = $result->render();
		if (substr($content, 0, 15) !== '<!DOCTYPE html>') {
			$this->assertSame(true, false, 'Unexpected content');
		}

		try {
			$this->picoController->getRoot('random_website');
			$this->assertSame(true, false, 'Should return Exception');
		} catch (\Exception $e) {}
	}

	public function testWebsiteDeletion()
	{
		$data = self::INFOS_WEBSITE1;
		$data['user_id'] = Env::ENV_TEST_USER1;

		try {
			$this->deleteWebsite($data);
		} catch (\Exception $e) {
			$this->assertSame(true, false, 'should not returns Exception - ' . $e->getMessage());
		}
	}

	/**
	 * @param array $data
	 */
	private function createWebsite(array $data)
	{
		$website = new Website($data);
		$this->websitesService->createWebsite($website);
	}

	/**
	 * @param array $data
	 */
	private function deleteWebsite(array $data)
	{
		$website = $this->websitesService->getWebsiteFromSite($data['site']);
		$website->assertOwnedBy($data['user_id']);

		$this->websitesService->deleteWebsite($website);
	}
}
