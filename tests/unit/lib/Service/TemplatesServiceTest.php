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
use OCA\CMSPico\Controller\SettingsController;
use OCA\CMSPico\Exceptions\TemplateNotFoundException;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Tests\Env;
use PHPUnit\Framework\TestCase;

class TemplatesServiceTest extends TestCase
{
	/** @var FileService */
	private $fileService;

	/** @var SettingsController */
	private $settingsController;

	/** @var TemplatesService */
	private $templatesService;

	protected function setUp(): void
	{
		Env::setUser(Env::ENV_TEST_USER1);
		Env::logout();

		$app = new Application();
		$container = $app->getContainer();

		$this->fileService = $container->query(FileService::class);
		$this->templatesService = $container->query(TemplatesService::class);
		$this->settingsController = $container->query(SettingsController::class);
	}

	protected function tearDown(): void
	{
		Env::setUser(Env::ENV_TEST_USER1);
		Env::logout();
	}

	public function testTemplates()
	{
		if (file_exists($this->fileService->getAppDataFolderPath('templates', true) . 'this_is_a_template')) {
			rmdir($this->fileService->getAppDataFolderPath('templates', true) . 'this_is_a_template');
		}

		$this->assertCount(2, $this->templatesService->getTemplates());
		$this->assertCount(0, $this->templatesService->getCustomTemplates());
		$this->assertCount(0, $this->templatesService->getNewCustomTemplates());

		mkdir($this->fileService->getAppDataFolderPath('templates', true) . 'this_is_a_template');
		$this->assertCount(2, $this->templatesService->getTemplates());
		$this->assertCount(0, $this->templatesService->getCustomTemplates());
		$this->assertCount(1, $this->templatesService->getNewCustomTemplates());

		try {
			$this->templatesService->assertValidTemplate('this_is_a_template');
			$this->assertSame(true, false, 'should return an exception');
		} catch (TemplateNotFoundException $e) {
		} catch (\Exception $e) {
			$this->assertSame(true, false, 'should return TemplateDoesNotExistException');
		}

		$this->settingsController->addCustomTemplate('this_is_a_template');
		$this->assertCount(3, $this->templatesService->getTemplates());
		$this->assertCount(1, $this->templatesService->getCustomTemplates());
		$this->assertCount(0, $this->templatesService->getNewCustomTemplates());

		$this->templatesService->assertValidTemplate('this_is_a_template');

		$this->settingsController->removeCustomTemplate('this_is_a_template');
		$this->assertCount(2, $this->templatesService->getTemplates());
		$this->assertCount(0, $this->templatesService->getCustomTemplates());
		$this->assertCount(1, $this->templatesService->getNewCustomTemplates());

		rmdir($this->fileService->getAppDataFolderPath('templates', true) . 'this_is_a_template');
		$this->assertCount(2, $this->templatesService->getTemplates());
		$this->assertCount(0, $this->templatesService->getCustomTemplates());
		$this->assertCount(0, $this->templatesService->getNewCustomTemplates());

		try {
			$this->templatesService->assertValidTemplate('this_is_a_template');
			$this->assertSame(true, false, 'should return an exception');
		} catch (TemplateNotFoundException $e) {
		} catch (\Exception $e) {
			$this->assertSame(true, false, 'should return TemplateDoesNotExistException');
		}
	}
}
