<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\CMSPico\Db;


use OCA\CMSPico\Exceptions\WebsiteDoesNotExistException;
use OCA\CMSPico\Model\Website;

class WebsitesRequest extends WebsitesRequestBuilder {


	/**
	 * @param Website $website
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function create(Website $website) {
		try {
			$qb = $this->getWebsitesInsertSql();
			$qb->setValue('name', $qb->createNamedParameter($website->getName()))
			   ->setValue('user_id', $qb->createNamedParameter($website->getUserId()))
			   ->setValue('site', $qb->createNamedParameter($website->getSite()))
			   ->setValue('type', $qb->createNamedParameter($website->getType()))
			   ->setValue('options', $qb->createNamedParameter($website->getOptions(true)))
			   ->setValue('path', $qb->createNamedParameter($website->getPath()));

			$qb->execute();

			return true;
		} catch (\Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param Website $website
	 */
	public function update(Website $website) {

		$qb = $this->getWebsitesUpdateSql();
		$qb->set('name', $qb->createNamedParameter($website->getName()));
		$qb->set('user_id', $qb->createNamedParameter($website->getUserId()));
		$qb->set('site', $qb->createNamedParameter($website->getSite()));
		$qb->set('type', $qb->createNamedParameter($website->getType()));
		$qb->set('options', $qb->createNamedParameter($website->getOptions(true)));
		$qb->set('path', $qb->createNamedParameter($website->getPath()));

		$this->limitToId($qb, $website->getId());

		$qb->execute();
	}


	/**
	 * @param Website $website
	 */
	public function delete(Website $website) {

		$qb = $this->getWebsitesDeleteSql();
		$this->limitToId($qb, $website->getId());

		$qb->execute();
	}


	/**
	 * @param string $userId
	 */
	public function deleteAllFromUser($userId) {

		$qb = $this->getWebsitesDeleteSql();
		$this->limitToUserId($qb, $userId);

		$qb->execute();
	}


	/**
	 * return list of websites from a user.
	 *
	 * @param string $userId
	 *
	 * @return Website[]
	 */
	public function getWebsitesFromUserId($userId) {
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
	 * return the website corresponding to the Id
	 *
	 * @param int $siteId
	 *
	 * @return Website
	 * @throws WebsiteDoesNotExistException
	 */
	public function getWebsiteFromId($siteId) {
		$qb = $this->getWebsitesSelectSql();
		$this->limitToId($qb, $siteId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new WebsiteDoesNotExistException($this->l10n->t('Website not found'));
		}

		return $this->parseWebsitesSelectSql($data);
	}


	/**
	 * return the website corresponding to the site/url
	 *
	 * @param string $site
	 *
	 * @return Website
	 * @throws WebsiteDoesNotExistException
	 */
	public function getWebsiteFromSite($site) {
		$qb = $this->getWebsitesSelectSql();
		$this->limitToSite($qb, $site);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new WebsiteDoesNotExistException($this->l10n->t('Website not found'));
		}

		return $this->parseWebsitesSelectSql($data);
	}

}