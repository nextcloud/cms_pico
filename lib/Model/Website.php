<?php

namespace OCA\CMSPico\Model;

use OC\Files\Filesystem;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\CheckCharsException;
use OCA\CMSPico\Exceptions\MinCharsException;
use OCA\CMSPico\Service\MiscService;

class Website implements \JsonSerializable {


	const TYPE_PUBLIC = 1;
	const TYPE_PRIVATE = 2;

	const SITE_LENGTH_MIN = 3;
	const NAME_LENGTH_MIN = 5;

	/** @var IL10N */
	private $l10n;

	/** @var int */
	private $id;

	/** @var string */
	private $userId;

	/** @var string */
	private $name;

	/** @var string */
	private $site;

	/** @var int */
	private $type = self::TYPE_PUBLIC;

	/** @var array */
	private $options = [];

	/** @var string */
	private $path;

	/** @var int */
	private $creation;

	/** @var string */
	private $viewer;

	/** @var string */
	private $templateSource;

	public function __construct() {
		$this->l10n = \OC::$server->getL10N(Application::APP_NAME);
	}


	/**
	 * @param int $id
	 *
	 * @return $this
	 */
	public function setId($id) {
		$this->id = (int)$id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setName($name) {
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}


	/**
	 * @param string $userId
	 *
	 * @return $this
	 */
	public function setUserId($userId) {
		$this->userId = $userId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserId() {
		return $this->userId;
	}


	/**
	 * @param string $site
	 *
	 * @return $this
	 */
	public function setSite($site) {
		$this->site = $site;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSite() {
		return $this->site;
	}


	/**
	 * @param int $type
	 *
	 * @return $this
	 */
	public function setType($type) {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * @param array|string $options
	 *
	 * @return Website
	 */
	public function setOptions($options) {
		if (!is_array($options)) {
			$options = json_decode($options);
		}

		if ($options === null) {
			return $this;
		}

		$this->options = $options;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}


	/**
	 * @param string $path
	 *
	 * @return $this
	 */
	public function setPath($path) {
		MiscService::endSlash($path);
		$this->path = $path;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	public function getAbsolutePath() {
		Filesystem::init($this->getUserId(), $this->getUserId() . '/files/');

		return Filesystem::getLocalFile($this->getPath());
	}


	/**
	 * @param int $creation
	 *
	 * @return $this
	 */
	public function setCreation($creation) {
		if ($creation === null) {
			return $this;
		}

		$this->creation = $creation;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCreation() {
		return $this->creation;
	}


	/**
	 * @param string $viewer
	 */
	public function setViewer($viewer) {
		$this->viewer = $viewer;
	}

	/**
	 * @return string
	 */
	public function getViewer() {
		return $this->viewer;
	}


	/**
	 * @param string $source
	 */
	public function setTemplateSource($source) {
		$this->templateSource = $source;
	}

	/**
	 * @return string
	 */
	public function getTemplateSource() {
		return $this->templateSource;
	}


	public function hasToBeFilledWithValidEntries() {

		if (strlen($this->getSite()) < self::SITE_LENGTH_MIN) {
			throw new MinCharsException($this->l10n->t('The address of the website must be longer'));
		}

		if (strlen($this->getName()) < self::NAME_LENGTH_MIN) {
			throw new MinCharsException($this->l10n->t('The name of the website must be longer'));
		}

		if (MiscService::checkChars($this->getSite(), MiscService::ALPHA_NUMERIC_SCORES) === false) {
			throw new CheckCharsException(
				$this->l10n->t('The address of the website can only contains alpha numeric chars')
			);
		}
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return array(
			'id'       => $this->getId(),
			'name'     => $this->getName(),
			'user_id'  => $this->getUserId(),
			'site'     => $this->getSite(),
			'type'     => $this->getType(),
			'options'  => $this->getOptions(),
			'path'     => $this->getPath(),
			'creation' => $this->getCreation()
		);
	}


	/**
	 * @param array $arr
	 *
	 * @return null|Website
	 */
	public static function fromArray($arr) {
		if (!is_array($arr)) {
			return null;
		}

		$website = new Website();

		$website->setId($arr['id'])
				->setName($arr['name'])
				->setUserId($arr['user_id'])
				->setSite($arr['site'])
				->setType($arr['type'])
				->setOptions($arr['options'])
				->setPath($arr['path'])
				->setCreation($arr['creation']);

		return $website;
	}


	/**
	 * @param $json
	 *
	 * @return null|Website
	 */
	public static function fromJSON($json) {
		return self::fromArray(json_decode($json, true));
	}

}