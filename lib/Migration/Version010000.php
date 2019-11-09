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
use OCA\CMSPico\Db\CoreRequestBuilder;
use OCA\CMSPico\Model\Template;
use OCA\CMSPico\Model\Theme;
use OCA\CMSPico\Model\WebsiteCore;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\PicoService;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Service\ThemesService;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010000 extends SimpleMigrationStep
{
	/** @var IDBConnection */
	private $databaseConnection;

	/** @var ConfigService */
	private $configService;

	/** @var TemplatesService */
	private $templatesService;

	/** @var ThemesService */
	private $themesService;

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
		$this->configService = \OC::$server->query(ConfigService::class);
		$this->templatesService = \OC::$server->query(TemplatesService::class);
		$this->themesService = \OC::$server->query(ThemesService::class);
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
		// check app dependencies
		$this->miscService->checkComposer();
		$this->miscService->checkPublicFolder();

		// update from cms_pico v0.9
		// migrate the app config of custom templates and themes
		$this->migrateCustomTemplates();
		$this->migrateCustomThemes();

		// migrate old copies of system templates and themes in Nextcloud's data dir
		$this->migrateSystemTemplates();
		$themesMigrationMap = $this->migrateSystemThemes();

		// migrate cms_pico_websites database table
		$this->migratePrivateWebsites($themesMigrationMap);
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
	 * @return array<string,string>
	 */
	private function migrateSystemTemplates()
	{
		$templatesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_TEMPLATES);

		$templates = $this->templatesService->getTemplates();
		$systemTemplates = $this->templatesService->getSystemTemplates();

		$templatesMigrationMap = [];
		foreach ($templatesFolder as $templateFolder) {
			$templateName = $templateFolder->getName();
			if ($templateFolder->isFolder() && isset($systemTemplates[$templateName])) {
				$newTemplateName = $templateName . '-v0.9';
				for ($i = 1; isset($templates[$newTemplateName]) || $templatesFolder->exists($newTemplateName); $i++) {
					$newTemplateName = $templateName . '-v0.9-dup' . $i;
				}

				$templateFolder->rename($newTemplateName);
				$this->templatesService->registerCustomTemplate($newTemplateName);
				$templatesMigrationMap[$templateName] = $newTemplateName;
			}
		}

		return $templatesMigrationMap;
	}

	/**
	 * @return array<string,string>
	 */
	private function migrateSystemThemes()
	{
		$themesFolder = $this->fileService->getAppDataFolder(PicoService::DIR_THEMES);

		$themes = $this->themesService->getThemes();
		$systemThemes = $this->themesService->getSystemThemes();

		$themesMigrationMap = [];
		foreach ($themesFolder as $themeFolder) {
			$themeName = $themeFolder->getName();
			if ($themeFolder->isFolder() && isset($systemThemes[$themeName])) {
				$newThemeName = $themeName . '-v0.9';
				for ($i = 1; isset($themes[$newThemeName]) || $themesFolder->exists($newThemeName); $i++) {
					$newThemeName = $themeName . '-v0.9-dup' . $i;
				}

				$themeFolder->rename($newThemeName);
				$this->themesService->publishCustomTheme($newThemeName);
				$themesMigrationMap[$themeName] = $newThemeName;
			}
		}

		return $themesMigrationMap;
	}

	/**
	 * @param array $themesMigrationMap
	 */
	private function migratePrivateWebsites(array $themesMigrationMap)
	{
		$qbUpdate = $this->databaseConnection->getQueryBuilder();
		$qbUpdate
			->update(CoreRequestBuilder::TABLE_WEBSITES, 'w')
			->set('w.theme', $qbUpdate->createParameter('theme'))
			->set('w.type', $qbUpdate->createParameter('type'))
			->set('w.options', $qbUpdate->createParameter('options'))
			->where($qbUpdate->expr()->eq('w.id', $qbUpdate->createParameter('id')));

		$selectCursor = $this->databaseConnection->getQueryBuilder()
			->select('w.id', 'w.theme', 'w.type', 'w.options')
			->from(CoreRequestBuilder::TABLE_WEBSITES, 'w')
			->execute();

		while ($data = $selectCursor->fetch()) {
			$websiteTheme = $themesMigrationMap[$data['theme']] ?? $data['theme'];

			$websiteType = $data['type'] ?: WebsiteCore::TYPE_PUBLIC;
			$websiteOptions = $data['options'] ? json_decode($data['options'], true) : [];
			if (isset($websiteOptions['private'])) {
				$websiteType = $websiteOptions['private'] ? WebsiteCore::TYPE_PRIVATE : WebsiteCore::TYPE_PUBLIC;
				unset($websiteOptions['private']);
			}

			$qbUpdate->setParameters([
				'id' => $data['id'],
				'theme' => $websiteTheme,
				'type' => $websiteType,
				'options' => json_encode($websiteOptions),
			]);

			$qbUpdate->execute();
		}

		$selectCursor->closeCursor();
	}
}
