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

class WebsitesRequest extends WebsitesRequestBuilder
{
	/**
	 * @param Website $website
	 */
	public function create(Website $website): void
	{
		$qb = $this->getWebsitesInsertSql();
		$qb
			->setValue('name', $qb->createNamedParameter($website->getName()))
			->setValue('user_id', $qb->createNamedParameter($website->getUserId()))
			->setValue('site', $qb->createNamedParameter($website->getSite()))
			->setValue('theme', $qb->createNamedParameter($website->getTheme()))
			->setValue('type', $qb->createNamedParameter($website->getType()))
			->setValue('options', $qb->createNamedParameter($website->getOptionsJSON()))
			->setValue('path', $qb->createNamedParameter($website->getPath()));

		$qb->execute();
	}

	/**
	 * @param Website $website
	 */
	public function update(Website $website): void
	{
		$qb = $this->getWebsitesUpdateSql();
		$qb
			->set('name', $qb->createNamedParameter($website->getName()))
			->set('user_id', $qb->createNamedParameter($website->getUserId()))
			->set('site', $qb->createNamedParameter($website->getSite()))
			->set('theme', $qb->createNamedParameter($website->getTheme()))
			->set('type', $qb->createNamedParameter($website->getType()))
			->set('options', $qb->createNamedParameter($website->getOptionsJSON()))
			->set('path', $qb->createNamedParameter($website->getPath()));

		$this->limitToId($qb, $website->getId());

		$qb->execute();
	}

	/**
	 * @param Website $website
	 */
	public function delete(Website $website): void
	{
		$qb = $this->getWebsitesDeleteSql();
		$this->limitToId($qb, $website->getId());

		$qb->execute();
	}

	/**
	 * @param string $userId
	 */
	public function deleteAllFromUserId(string $userId): void
	{
		$qb = $this->getWebsitesDeleteSql();
		$this->limitToUserId($qb, $userId);

		$qb->execute();
	}

	/**
	 * @param string $userId
	 *
	 * @return Website[]
	 */
	public function getWebsitesFromUserId(string $userId): array
	{
		$qb = $this->getWebsitesSelectSql();
		$this->limitToUserId($qb, $userId);

		$websites = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$websites[] = $this->parseWebsitesSelectSql($data);
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
	public function getWebsiteFromId(int $siteId): Website
	{
		$qb = $this->getWebsitesSelectSql();
		$this->limitToId($qb, $siteId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new WebsiteNotFoundException();
		}

		return $this->parseWebsitesSelectSql($data);
	}

	/**
	 * @param string $site
	 *
	 * @return Website
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromSite(string $site): Website
	{
		$qb = $this->getWebsitesSelectSql();
		$this->limitToSite($qb, $site);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new WebsiteNotFoundException();
		}

		return $this->parseWebsitesSelectSql($data);
	}
}
