<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
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

namespace OCA\CMSPico\Migration;

use OC\Encryption\Manager as EncryptionManager;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\FilesystemEncryptedException;
use OCA\CMSPico\Exceptions\FilesystemNotWritableException;
use OCA\CMSPico\Model\Plugin;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\PicoService;
use OCP\DB\ISchemaWrapper;
use OCP\Files\AlreadyExistsException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010000 extends SimpleMigrationStep
{
	/** @var IL10N */
	private $l10n;

	/** @var EncryptionManager */
	private $encryptionManager;

	/** @var ConfigService */
	private $configService;

	/** @var FileService */
	private $fileService;

	/** @var MiscService */
	private $miscService;

	/**
	 * Version010000 constructor.
	 */
	public function __construct()
	{
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);
		$this->encryptionManager = \OC::$server->getEncryptionManager();
		$this->configService = \OC::$server->query(ConfigService::class);
		$this->fileService = \OC::$server->query(FileService::class);
		$this->miscService = \OC::$server->query(MiscService::class);
	}

	/**
	 * @param IOutput  $output
	 * @param \Closure $schemaClosure
	 * @param array    $options
	 *
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ISchemaWrapper
	{
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('cms_pico_websites')) {
			$table = $schema->createTable('cms_pico_websites');

			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('site', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('theme', 'string', [
				'notnull' => true,
				'length' => 63,
				'default' => 'default',
			]);
			$table->addColumn('type', 'smallint', [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('options', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('path', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('creation', 'datetime', [
				'notnull' => false,
			]);

			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}

	/**
	 * @param IOutput  $output
	 * @param \Closure $schemaClosure
	 * @param array    $options
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options)
	{
		$this->createPublicFolder();
		$this->checkEncryptedFilesystem();
		$this->migrateCustomPlugins();
	}

	/**
	 * @throws FilesystemNotWritableException
	 */
	private function createPublicFolder()
	{
		$publicFolder = $this->fileService->getPublicFolder();

		try {
			try {
				$publicThemesFolder = $publicFolder->newFolder(PicoService::DIR_THEMES);
			} catch (AlreadyExistsException $e) {
				$publicThemesFolder = $publicFolder->get(PicoService::DIR_THEMES);
			}

			$publicThemesTestFileName = $this->miscService->getRandom(10, 'tmp', Application::APP_NAME . '-themes_test');
			$publicThemesTestFile = $publicThemesFolder->newFile($publicThemesTestFileName);
			$publicThemesTestFile->delete();

			try {
				$publicPluginsFolder = $publicFolder->newFolder(PicoService::DIR_PLUGINS);
			} catch (AlreadyExistsException $e) {
				$publicPluginsFolder = $publicFolder->get(PicoService::DIR_PLUGINS);
			}

			$publicPluginsTestFileName = $this->miscService->getRandom(10, 'tmp', Application::APP_NAME . '-plugins_test');
			$publicPluginsTestFile = $publicPluginsFolder->newFile($publicPluginsTestFileName);
			$publicPluginsTestFile->delete();
		} catch (NotPermittedException $e) {
			$appDataPublicPath = \OC_App::getAppPath(Application::APP_NAME) . '/appdata_public';
			$dataPath = $this->configService->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');

			throw new FilesystemNotWritableException($this->l10n->t(
				'Failed to enable Pico CMS for Nextcloud: The webserver has no permission to create files and '
						. 'folders below "%s". Make sure to give the webserver write access to this directory by '
						. 'changing its permissions and ownership to the same as of your "%s" directory. Then try '
						. 'again enabling Pico CMS for Nextcloud.',
				[
					$this->miscService->getRelativePath($appDataPublicPath) . '/',
					$this->miscService->getRelativePath($dataPath) . '/'
				]
			));
		}
	}

	/**
	 * @throws FilesystemEncryptedException
	 */
	private function checkEncryptedFilesystem()
	{
		if ($this->encryptionManager->isEnabled()) {
			throw new FilesystemEncryptedException($this->l10n->t(
				'Failed to enable Pico CMS for Nextcloud: You can\'t host websites on a encrypted Nextcloud.'
			));
		}
	}

	/**
	 * @return void
	 */
	private function migrateCustomPlugins()
	{
		$customPluginsJson = $this->configService->getAppValue(ConfigService::CUSTOM_PLUGINS);
		$customPlugins = $customPluginsJson ? json_decode($customPluginsJson, true) : [];

		$newCustomPlugins = [];
		foreach ($customPlugins as $pluginName) {
			$newCustomPlugins[$pluginName] = [
				'name' => $pluginName,
				'type' => Plugin::PLUGIN_TYPE_CUSTOM,
				'compat' => true
			];
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_PLUGINS, json_encode($newCustomPlugins));
	}
}
