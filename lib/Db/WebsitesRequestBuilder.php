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

namespace OCA\CMSPico\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

class WebsitesRequestBuilder extends CoreRequestBuilder
{
	/**
	 * @return IQueryBuilder
	 */
	protected function getWebsitesInsertSql(): IQueryBuilder
	{
		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->insert(self::TABLE_WEBSITES)
			->setValue('creation', $qb->createFunction('NOW()'));

		return $qb;
	}

	/**
	 * @return IQueryBuilder
	 */
	protected function getWebsitesUpdateSql(): IQueryBuilder
	{
		return $this->dbConnection->getQueryBuilder()
			->update(self::TABLE_WEBSITES);
	}

	/**
	 * @return IQueryBuilder
	 */
	protected function getWebsitesSelectSql(): IQueryBuilder
	{
		return $this->dbConnection->getQueryBuilder()
			->select('*')
			->from(self::TABLE_WEBSITES);
	}

	/**
	 * @return IQueryBuilder
	 */
	protected function getWebsitesDeleteSql(): IQueryBuilder
	{
		return $this->dbConnection->getQueryBuilder()
			->delete(self::TABLE_WEBSITES);
	}
}
