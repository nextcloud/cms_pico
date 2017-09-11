<?php


namespace OCA\CMSPico\Model;

use OCA\CMSPico\Service\MiscService;

class WebsiteCore implements \JsonSerializable {

	const TYPE_PUBLIC = 1;
	const TYPE_PRIVATE = 2;

	const SITE_LENGTH_MIN = 3;
	const NAME_LENGTH_MIN = 5;


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
	 * @param string $key
	 * @param string $value
	 */
	public function setOption($key, $value) {
		$this->options[$key] = $value;
	}


	/**
	 * @param array|string $options
	 *
	 * @return $this
	 */
	public function setOptions($options) {
		if (!is_array($options)) {
			$options = json_decode($options, true);
		}

		if ($options === null) {
			return $this;
		}

		$this->options = $options;

		return $this;
	}


	/**
	 * @param bool $json
	 *
	 * @return array
	 */
	public function getOptions($json = false) {
		if ($json === true) {
			return json_encode($this->options);
		}

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