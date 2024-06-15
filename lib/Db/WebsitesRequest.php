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

use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Model\Website;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class WebsitesRequest
{
	/** @var string */
	public const TABLE_NAME = 'cms_pico_websites';

	/** @var IDBConnection */
	protected $dbConnection;

	/**
	 * CoreRequestBuilder constructor.
	 *
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection)
	{
		$this->dbConnection = $connection;
	}

	/**
	 * @param Website $website
	 */
	public function create(Website $website): void
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->insert(WebsitesRequest::TABLE_NAME);

		$qb
			->setValue('name', $qb->createNamedParameter($website->getName()))
			->setValue('user_id', $qb->createNamedParameter($website->getUserId()))
			->setValue('site', $qb->createNamedParameter($website->getSite()))
			->setValue('theme', $qb->createNamedParameter($website->getTheme()))
			->setValue('type', $qb->createNamedParameter($website->getType()))
			->setValue('options', $qb->createNamedParameter($website->getOptionsJSON()))
			->setValue('path', $qb->createNamedParameter($website->getPath()))
			->setValue('creation', $qb->createFunction('NOW()'));

		$qb->execute();

		$website->setId($qb->getLastInsertId());
	}

	/**
	 * @param Website $website
	 */
	public function update(Website $website): void
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->update(WebsitesRequest::TABLE_NAME);

		$qb
			->set('name', $qb->createNamedParameter($website->getName()))
			->set('user_id', $qb->createNamedParameter($website->getUserId()))
			->set('site', $qb->createNamedParameter($website->getSite()))
			->set('theme', $qb->createNamedParameter($website->getTheme()))
			->set('type', $qb->createNamedParameter($website->getType()))
			->set('options', $qb->createNamedParameter($website->getOptionsJSON()))
			->set('path', $qb->createNamedParameter($website->getPath()));

		$this->limitToField($qb, 'id', $website->getId());

		$qb->execute();
	}

	/**
	 * @param Website $website
	 */
	public function delete(Website $website): void
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->delete(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'id', $website->getId());

		$qb->execute();
	}

	/**
	 * @param string $userId
	 */
	public function deleteAllFromUserId(string $userId): void
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->delete(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'user_id', $userId);

		$qb->execute();
	}

	/**
	 * @return Website[]
	 */
	public function getWebsites(): array
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->select('*')
			->from(WebsitesRequest::TABLE_NAME);

		$websites = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$websites[] = $this->createInstance($data);
		}
		$cursor->closeCursor();

		return $websites;
	}

	/**
	 * @param string $userId
	 *
	 * @return Website[]
	 */
	public function getWebsitesFromUserId(string $userId): array
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->select('*')
			->from(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'user_id', $userId);

		$websites = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$websites[] = $this->createInstance($data);
		}
		$cursor->closeCursor();

		return $websites;
	}

	/**
	 * @param int $siteId
	 *
	 * @return Website
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromId(int $id): Website
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->select('*')
			->from(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'id', $id);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new WebsiteNotFoundException('#' . $id);
		}

		return $this->createInstance($data);
	}

	/**
	 * @param string $site
	 *
	 * @return Website
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromSite(string $site): Website
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->select('*')
			->from(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'site', $site);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new WebsiteNotFoundException($site);
		}

		return $this->createInstance($data);
	}

	/**
	 * @param array $data
	 *
	 * @return Website
	 */
	private function createInstance(array $data): Website
	{
		return new Website($data);
	}

	/**
	 * @param IQueryBuilder $qb
	 * @param string        $field
	 * @param mixed         $value
	 */
	private function limitToField(IQueryBuilder $qb, string $field, $value): void
	{
		$qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value)));
	}
}
