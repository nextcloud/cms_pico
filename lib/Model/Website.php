<?php

namespace OCA\CMSPico\Model;

class Website implements \JsonSerializable {

	/** @var int */
	private $id;

	/** @var string */
	private $userId;

	/** @var string */
	private $site;

	/** @var int */
	private $type = Webpage::TYPE_PUBLIC;

	/** @var array */
	private $options = [];

	/** @var string */
	private $path;

	/** @var int */
	private $creation;

	/** @var string */
	private $viewer;

	public function __construct() {
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
		$this->path = $path;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
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
	 * @return array
	 */
	public function jsonSerialize() {
		return array(
			'id'       => $this->getId(),
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