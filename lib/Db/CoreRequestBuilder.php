<?php
/**
 * Created by PhpStorm.
 * User: maxence
 * Date: 7/4/17
 * Time: 5:01 PM
 */

namespace OCA\CMSPico\Db;


use Doctrine\DBAL\Query\QueryBuilder;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

class CoreRequestBuilder {

	const TABLE_WEBSITES = 'cms_pico_websites';

	const NC_TABLE_GROUP_USER = 'group_user';

	/** @var IDBConnection */
	protected $dbConnection;

	/** @var IL10N */
	protected $l10n;

	/** @var ConfigService */
	protected $configService;

	/** @var MiscService */
	protected $miscService;

	/** @var string */
	protected $default_select_alias;


	/**
	 * CoreRequestBuilder constructor.
	 *
	 * @param IL10N $l10n
	 * @param IDBConnection $connection
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, ConfigService $configService, MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->dbConnection = $connection;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * Limit the request by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $id
	 */
	protected function limitToId(IQueryBuilder &$qb, $id) {
		$this->limitToDBField($qb, 'id', $id);
	}


	/**
	 * Limit the request to the User by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param $userId
	 */
	protected function limitToUserId(IQueryBuilder &$qb, $userId) {
		$this->limitToDBField($qb, 'user_id', $userId);
	}


	/**
	 * Limit to the path
	 *
	 * @param IQueryBuilder $qb
	 * @param $userId
	 */
	protected function limitToPath(IQueryBuilder &$qb, $userId) {
		$this->limitToDBField($qb, 'path', $userId);
	}


	/**
	 * Limit to the site
	 *
	 * @param IQueryBuilder $qb
	 * @param $userId
	 */
	protected function limitToSite(IQueryBuilder &$qb, $userId) {
		$this->limitToDBField($qb, 'site', $userId);
	}


	/**
	 * Limit the request to the Group by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $groupId
	 */
	protected function limitToGroupId(IQueryBuilder &$qb, $groupId) {
		$this->limitToDBField($qb, 'group_id', $groupId);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string|integer $value
	 */
	private function limitToDBField(IQueryBuilder &$qb, $field, $value) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';
		$qb->andWhere($expr->eq($pf . $field, $qb->createNamedParameter($value)));
	}

}



