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

use Doctrine\DBAL\Schema\SchemaException;
use OC\Encryption\Manager as EncryptionManager;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Db\CoreRequestBuilder;
use OCA\CMSPico\Exceptions\ComposerException;
use OCA\CMSPico\Exceptions\FilesystemNotWritableException;
use OCA\CMSPico\Model\Plugin;
use OCA\CMSPico\Model\Template;
use OCA\CMSPico\Model\Theme;
use OCA\CMSPico\Model\WebsiteCore;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\PicoService;
use OCP\DB\ISchemaWrapper;
use OCP\Files\AlreadyExistsException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010000 extends SimpleMigrationStep
{
	/** @var IDBConnection */
	private $databaseConnection;

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
		$this->databaseConnection = \OC::$server->getDatabaseConnection();
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

		try {
			$table = $schema->getTable(CoreRequestBuilder::TABLE_WEBSITES);
		} catch (SchemaException $e) {
			$table = $schema->createTable(CoreRequestBuilder::TABLE_WEBSITES);

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
				'length' => 64,
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

		$themeColumn = $table->getColumn('theme');
		if ($themeColumn->getLength() < 64) {
			$themeColumn->setLength(64);
		}

		if (!$table->hasIndex('user_id')) {
			$table->addIndex([ 'user_id' ], 'user_id');
		}

		if (!$table->hasIndex('site')) {
			$table->addIndex([ 'site' ], 'site');
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
		$this->migratePrivateWebsites();
		$this->checkComposer();
		$this->createPublicFolder();

		$this->migrateCustomTemplates();
		$this->migrateCustomThemes();
		$this->migrateCustomPlugins();
	}

	/**
	 * @return void
	 */
	private function migratePrivateWebsites()
	{
		$qbUpdate = $this->databaseConnection->getQueryBuilder();
		$qbUpdate
			->update(CoreRequestBuilder::TABLE_WEBSITES, 'w')
			->set('w.type', $qbUpdate->createParameter('type'))
			->set('w.options', $qbUpdate->createParameter('options'))
			->where($qbUpdate->expr()->eq('w.id', $qbUpdate->createParameter('id')));

		$selectCursor = $this->databaseConnection->getQueryBuilder()
			->select('w.id', 'w.type', 'w.options')
			->from(CoreRequestBuilder::TABLE_WEBSITES, 'w')
			->execute();

		while ($data = $selectCursor->fetch()) {
			$websiteOptions = $data['options'] ? json_decode($data['options'], true) : [];
			if (isset($websiteOptions['private'])) {
				$websiteType = $websiteOptions['private'] ? WebsiteCore::TYPE_PRIVATE : WebsiteCore::TYPE_PUBLIC;
				unset($websiteOptions['private']);

				$qbUpdate->setParameters([
					'id' => $data['id'],
					'type' => $websiteType,
					'options' => json_encode($websiteOptions)
				]);

				$qbUpdate->execute();
			}
		}

		$selectCursor->closeCursor();
	}

	/**
	 * @throws ComposerException
	 */
	private function checkComposer()
	{
		$appPath = Application::getAppPath();
		if (!is_file($appPath . '/vendor/autoload.php')) {
			try {
				$relativeAppPath = $this->miscService->getRelativePath($appPath) . '/';
			} catch (InvalidPathException $e) {
				$relativeAppPath = 'apps/' . Application::APP_NAME . '/';
			}

			throw new ComposerException($this->l10n->t(
				'Failed to enable Pico CMS for Nextcloud: Couldn\'t find "%s". Make sure to install the app\'s '
						. 'dependencies by executing `composer install` in the app\'s install directory below "%s". '
						. 'Then try again enabling Pico CMS for Nextcloud.',
				[ $relativeAppPath . 'vendor/autoload.php', $relativeAppPath ]
			));
		}
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
				$publicThemesFolder = $publicFolder->getFolder(PicoService::DIR_THEMES);
			}

			$publicThemesTestFileName = $this->miscService->getRandom(10, 'tmp', Application::APP_NAME . '-test');
			$publicThemesTestFile = $publicThemesFolder->newFile($publicThemesTestFileName);
			$publicThemesTestFile->delete();

			try {
				$publicPluginsFolder = $publicFolder->newFolder(PicoService::DIR_PLUGINS);
			} catch (AlreadyExistsException $e) {
				$publicPluginsFolder = $publicFolder->getFolder(PicoService::DIR_PLUGINS);
			}

			$publicPluginsTestFileName = $this->miscService->getRandom(10, 'tmp', Application::APP_NAME . '-test');
			$publicPluginsTestFile = $publicPluginsFolder->newFile($publicPluginsTestFileName);
			$publicPluginsTestFile->delete();
		} catch (NotPermittedException $e) {
			try {
				$appDataPublicPath = Application::getAppPath() . '/appdata_public';
				$appDataPublicPath = $this->miscService->getRelativePath($appDataPublicPath) . '/';
			} catch (InvalidPathException $e) {
				$appDataPublicPath = 'apps/' . Application::APP_NAME . '/appdata_public/';
			}

			try {
				$dataPath = $this->configService->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');
				$dataPath = $this->miscService->getRelativePath($dataPath) . '/';
			} catch (InvalidPathException $e) {
				$dataPath = 'data/';
			}

			throw new FilesystemNotWritableException($this->l10n->t(
				'Failed to enable Pico CMS for Nextcloud: The webserver has no permission to create files and '
						. 'folders below "%s". Make sure to give the webserver write access to this directory by '
						. 'changing its permissions and ownership to the same as of your "%s" directory. Then try '
						. 'again enabling Pico CMS for Nextcloud.',
				[ $appDataPublicPath, $dataPath ]
			));
		}
	}

	/**
	 * @return void
	 */
	private function migrateCustomTemplates()
	{
		$customTemplatesJson = $this->configService->getAppValue(ConfigService::CUSTOM_TEMPLATES);
		$customTemplates = $customTemplatesJson ? json_decode($customTemplatesJson, true) : [];

		$newCustomTemplates = [];
		foreach ($customTemplates as $templateName) {
			$newCustomTemplates[$templateName] = [
				'name' => $templateName,
				'type' => Template::TYPE_CUSTOM,
				'compat' => true
			];
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_TEMPLATES, json_encode($newCustomTemplates));
	}

	/**
	 * @return void
	 */
	private function migrateCustomThemes()
	{
		$customThemesJson = $this->configService->getAppValue(ConfigService::CUSTOM_THEMES);
		$customThemes = $customThemesJson ? json_decode($customThemesJson, true) : [];

		$newCustomThemes = [];
		foreach ($customThemes as $themeName) {
			$newCustomThemes[$themeName] = [
				'name' => $themeName,
				'type' => Theme::TYPE_CUSTOM,
				'compat' => true
			];
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_THEMES, json_encode($newCustomThemes));
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
				'type' => Plugin::TYPE_CUSTOM,
				'compat' => true
			];
		}

		$this->configService->setAppValue(ConfigService::CUSTOM_PLUGINS, json_encode($newCustomPlugins));
	}
}
