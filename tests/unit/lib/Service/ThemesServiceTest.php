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
use OCA\CMSPico\Controller\SettingsController;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Tests\Env;


class ThemesServiceTest extends \PHPUnit_Framework_TestCase {

	/** @var FileService */
	private $fileService;

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

		$this->fileService = $container->query(FileService::class);
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

		$this->assertCount(1, $this->themesService->getThemes());
		$this->assertCount(0, $this->themesService->getCustomThemes());
		$this->assertCount(0, $this->themesService->getNewCustomThemes());

		mkdir($this->fileService->getAppDataFolderPath('themes', true) . 'this_is_a_test');
		$this->assertCount(1, $this->themesService->getThemes());
		$this->assertCount(0, $this->themesService->getCustomThemes());
		$this->assertCount(1, $this->themesService->getNewCustomThemes());

		try {
			$this->themesService->assertValidTheme('this_is_a_test');
			$this->assertSame(true, false, 'should return an exception');
		} catch (ThemeNotFoundException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should return ThemeDoesNotExistException');
		}

		$this->settingsController->addCustomTheme('this_is_a_test');
		$this->assertCount(2, $this->themesService->getThemes());
		$this->assertCount(1, $this->themesService->getCustomThemes());
		$this->assertCount(0, $this->themesService->getNewCustomThemes());

		$this->themesService->assertValidTheme('this_is_a_test');

		$this->settingsController->removeCustomTheme('this_is_a_test');
		$this->assertCount(1, $this->themesService->getThemes());
		$this->assertCount(0, $this->themesService->getCustomThemes());
		$this->assertCount(1, $this->themesService->getNewCustomThemes());

		rmdir($this->fileService->getAppDataFolderPath('themes', true) . 'this_is_a_test');
		$this->assertCount(1, $this->themesService->getThemes());
		$this->assertCount(0, $this->themesService->getCustomThemes());
		$this->assertCount(0, $this->themesService->getNewCustomThemes());

		try {
			$this->themesService->assertValidTheme('this_is_a_test');
			$this->assertSame(true, false, 'should return an exception');
		} catch (ThemeNotFoundException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should return ThemeDoesNotExistException');
		}

	}

}
