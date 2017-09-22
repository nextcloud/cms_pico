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
use OCA\CMSPico\Exceptions\TemplateDoesNotExistException;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Tests\Env;


class TemplatesServiceTest extends \PHPUnit_Framework_TestCase {


	/** @var SettingsController */
	private $settingsController;

	/** @var TemplatesService */
	private $templatesService;


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

		$this->templatesService = $container->query(TemplatesService::class);
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
	public function testTemplates() {

		if (file_exists(WebsitesServiceTest::PICO_FOLDER . '/templates/this_is_a_template')) {
			rmdir(WebsitesServiceTest::PICO_FOLDER . '/templates/this_is_a_template');
		}

		$this->assertCount(1, $this->templatesService->getTemplatesList());
		$this->assertCount(0, $this->templatesService->getTemplatesList(true));
		$this->assertCount(0, $this->templatesService->getNewTemplatesList());

		mkdir(WebsitesServiceTest::PICO_FOLDER . '/templates/this_is_a_template');
		$this->assertCount(1, $this->templatesService->getTemplatesList());
		$this->assertCount(0, $this->templatesService->getTemplatesList(true));
		$this->assertCount(1, $this->templatesService->getNewTemplatesList());

		try {
			$this->templatesService->templateHasToExist('this_is_a_template');
			$this->assertSame(true, false, 'should return an exception');
		} catch (TemplateDoesNotExistException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should return TemplateDoesNotExistException');
		}

		$this->settingsController->addCustomTemplate('this_is_a_template');
		$this->assertCount(2, $this->templatesService->getTemplatesList());
		$this->assertCount(1, $this->templatesService->getTemplatesList(true));
		$this->assertCount(0, $this->templatesService->getNewTemplatesList());

		$this->templatesService->templateHasToExist('this_is_a_template');

		$this->settingsController->removeCustomTemplate('this_is_a_template');
		$this->assertCount(1, $this->templatesService->getTemplatesList());
		$this->assertCount(0, $this->templatesService->getTemplatesList(true));
		$this->assertCount(1, $this->templatesService->getNewTemplatesList());

		rmdir(WebsitesServiceTest::PICO_FOLDER . '/templates/this_is_a_template');
		$this->assertCount(1, $this->templatesService->getTemplatesList());
		$this->assertCount(0, $this->templatesService->getTemplatesList(true));
		$this->assertCount(0, $this->templatesService->getNewTemplatesList());

		try {
			$this->templatesService->templateHasToExist('this_is_a_template');
			$this->assertSame(true, false, 'should return an exception');
		} catch (TemplateDoesNotExistException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should return TemplateDoesNotExistException');
		}

	}

}