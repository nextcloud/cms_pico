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
use OCA\CMSPico\Controller\SettingsController;
use OCA\CMSPico\Exceptions\ThemeDoesNotExistException;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Tests\Env;


class ThemesServiceTest extends \PHPUnit_Framework_TestCase {


	/** @var SettingsController */
	private $settingsController;

	/** @var ThemesService */
	private $themesService;


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

		$this->themesService = $container->query(ThemesService::class);
		$this->settingsController = $container->query(SettingsController::class);
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
	public function testThemes() {

		$this->assertCount(1, $this->themesService->getThemesList());
		$this->assertCount(0, $this->themesService->getThemesList(true));
		$this->assertCount(0, $this->themesService->getNewThemesList());

		mkdir(WebsitesServiceTest::PICO_FOLDER . '/themes/this_is_a_test');
		$this->assertCount(1, $this->themesService->getThemesList());
		$this->assertCount(0, $this->themesService->getThemesList(true));
		$this->assertCount(1, $this->themesService->getNewThemesList());

		try {
			$this->themesService->hasToBeAValidTheme('this_is_a_test');
			$this->assertSame(true, false, 'should return an exception');
		} catch (ThemeDoesNotExistException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should return ThemeDoesNotExistException');
		}

		$this->settingsController->addCustomTheme('this_is_a_test');
		$this->assertCount(2, $this->themesService->getThemesList());
		$this->assertCount(1, $this->themesService->getThemesList(true));
		$this->assertCount(0, $this->themesService->getNewThemesList());

		$this->themesService->hasToBeAValidTheme('this_is_a_test');

		$this->settingsController->removeCustomTheme('this_is_a_test');
		$this->assertCount(1, $this->themesService->getThemesList());
		$this->assertCount(0, $this->themesService->getThemesList(true));
		$this->assertCount(1, $this->themesService->getNewThemesList());

		rmdir(WebsitesServiceTest::PICO_FOLDER . '/themes/this_is_a_test');
		$this->assertCount(1, $this->themesService->getThemesList());
		$this->assertCount(0, $this->themesService->getThemesList(true));
		$this->assertCount(0, $this->themesService->getNewThemesList());

		try {
			$this->themesService->hasToBeAValidTheme('this_is_a_test');
			$this->assertSame(true, false, 'should return an exception');
		} catch (ThemeDoesNotExistException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should return ThemeDoesNotExistException');
		}

	}

}