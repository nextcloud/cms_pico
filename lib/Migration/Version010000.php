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
use OCA\CMSPico\Db\WebsitesRequestBuilder;
use OCA\CMSPico\Service\MiscService;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010000 extends SimpleMigrationStep
{
	/** @var MiscService */
	private $miscService;

	/**
	 * Version010000 constructor.
	 */
	public function __construct()
	{
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
			$table = $schema->getTable(WebsitesRequestBuilder::TABLE_WEBSITES);
		} catch (SchemaException $e) {
			$table = $schema->createTable(WebsitesRequestBuilder::TABLE_WEBSITES);

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

		if (!$table->hasIndex(WebsitesRequestBuilder::TABLE_WEBSITES . '_user_id')) {
			$table->addIndex([ 'user_id' ], WebsitesRequestBuilder::TABLE_WEBSITES . '_user_id');
		}

		if (!$table->hasIndex(WebsitesRequestBuilder::TABLE_WEBSITES . '_site')) {
			$table->addIndex([ 'site' ], WebsitesRequestBuilder::TABLE_WEBSITES . '_site');
		}

		return $schema;
	}

	/**
	 * @param IOutput  $output
	 * @param \Closure $schemaClosure
	 * @param array    $options
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void
	{
		$this->miscService->checkComposer();
		$this->miscService->checkPublicFolder();
	}
}
