<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2022, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
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

use OCA\CMSPico\Db\WebsitesRequestBuilder;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010017 extends SimpleMigrationStep
{
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

		$table = $schema->getTable(WebsitesRequestBuilder::TABLE_WEBSITES);

		if ($table->hasIndex('user_id')) {
			$table->dropIndex('user_id');
		}

		if (!$table->hasIndex(WebsitesRequestBuilder::TABLE_WEBSITES . '_user_id')) {
			$table->addIndex([ 'user_id' ], WebsitesRequestBuilder::TABLE_WEBSITES . '_user_id');
		}

		if ($table->hasIndex('site')) {
			$table->dropIndex('site');
		}

		if (!$table->hasIndex(WebsitesRequestBuilder::TABLE_WEBSITES . '_site')) {
			$table->addIndex([ 'site' ], WebsitesRequestBuilder::TABLE_WEBSITES . '_site');
		}

		return $schema;
	}
}
