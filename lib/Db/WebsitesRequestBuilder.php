<?php

namespace OCA\CMSPico\Db;


use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

class WebsitesRequestBuilder extends CoreRequestBuilder {


	/**
	 * WebsitesRequestBuilder constructor.
	 *
	 * {@inheritdoc}
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, ConfigService $configService, MiscService $miscService
	) {
		parent::__construct($l10n, $connection, $configService, $miscService);
	}


	/**
	 * Base of the Sql Insert request
	 *
	 * @return IQueryBuilder
	 */
	protected function getWebsitesInsertSql() {
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
	protected function getWebsitesUpdateSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_WEBSITES);

		return $qb;
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getWebsitesSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('w.id', 'w.user_id', 'w.site', 'w.type', 'w.options', 'w.path', 'w.creation')
		   ->from(self::TABLE_WEBSITES, 'w');

		$this->default_select_alias = 'w';

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getWebsitesDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_WEBSITES);

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return Website
	 */
	protected function parseWebsitesSelectSql($data) {
		$website = new Website();
		$website->setId($data['id'])
			 ->setUserId($data['user_id'])
			 ->setSite($data['site'])
			 ->setType($data['type'])
			 ->setOptions($data['options'])
			 ->setPath($data['path'])
			 ->setCreation($data['creation']);

		return $website;
	}

}