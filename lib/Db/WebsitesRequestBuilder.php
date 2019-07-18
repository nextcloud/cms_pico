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

namespace OCA\CMSPico\Db;

use OCA\CMSPico\Model\Website;
use OCP\DB\QueryBuilder\IQueryBuilder;

class WebsitesRequestBuilder extends CoreRequestBuilder
{
	/**
	 * Base of the Sql Insert request
	 *
	 * @return IQueryBuilder
	 */
	protected function getWebsitesInsertSql()
	{
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_WEBSITES)
			->setValue('creation', $qb->createFunction('NOW()'));

		return $qb;
	}

	/**
	 * Base of the Sql Update request
	 *
	 * @return IQueryBuilder
	 */
	protected function getWebsitesUpdateSql()
	{
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_WEBSITES);

		return $qb;
	}

	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getWebsitesSelectSql()
	{
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('w.id', 'w.name', 'w.user_id', 'w.site', 'w.theme', 'w.type', 'w.options', 'w.path', 'w.creation')
			->from(self::TABLE_WEBSITES, 'w');

		$this->defaultSelectAlias = 'w';

		return $qb;
	}

	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getWebsitesDeleteSql()
	{
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_WEBSITES);

		return $qb;
	}

	/**
	 * @param array $data
	 *
	 * @return Website
	 */
	protected function parseWebsitesSelectSql($data)
	{
		return new Website($data);
	}
}
