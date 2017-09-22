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
	private $theme = 'default';

	/** @var string */
	private $site;

	/** @var int */
	private $type = self::TYPE_PUBLIC;

	/** @var array */
	private $options = [];

	/** @var string */
	private $path;

	/** @var string */
	private $page;

	/** @var int */
	private $creation;

	/** @var string */
	private $viewer;

	/** @var string */
	private $templateSource;


	public function __construct($data = '') {

		if (is_array($data)) {
			$this->fromArray($data);

			return;
		}

		$this->fromJSON($data);
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
	 * @param $theme
	 *
	 * @return $this
	 */
	public function setTheme($theme) {
		$this->theme = $theme;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTheme() {
		return $this->theme;
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
	 *
	 * @return $this
	 */
	public function setOption($key, $value) {
		$this->options[$key] = $value;

		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function getOption($key) {
		if (!key_exists($key, $this->options)) {
			return '';
		}

		return (string)$this->options[$key];
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
		$this->path = MiscService::endSlash($path);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}


	/**
	 * @param string $page
	 *
	 * @return $this
	 */
	public function setPage($page) {
		$this->page = $page;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPage() {
		return $this->page;
	}


	/**
	 * @param int $creation
	 *
	 * @return $this
	 */
	public function setCreation($creation) {
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
	 *
	 * @return $this
	 */
	public function setViewer($viewer) {
		$this->viewer = $viewer;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getViewer() {
		return $this->viewer;
	}


	/**
	 * @param string $source
	 *
	 * @return $this
	 */
	public function setTemplateSource($source) {
		$this->templateSource = $source;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateSource() {
		return $this->templateSource;
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
			'page'     => $this->getPage(),
			'theme'    => $this->getTheme(),
			'type'     => $this->getType(),
			'options'  => $this->getOptions(),
			'path'     => $this->getPath(),
			'creation' => $this->getCreation()
		);
	}


	/**
	 * @param array $arr
	 *
	 * @return bool
	 */
	public function fromArray($arr) {
		if (!is_array($arr)) {
			return false;
		}

		MiscService::mustContains($arr, ['name', 'user_id', 'site', 'type', 'path']);

		$this->setId((int)MiscService::get($arr, 'id'))
			 ->setName($arr['name'])
			 ->setUserId($arr['user_id'])
			 ->setSite($arr['site'])
			 ->setPage($arr['page'])
			 ->setTheme(MiscService::get($arr, 'theme', 'default'))
			 ->setType($arr['type'])
			 ->setOptions(MiscService::get($arr, 'options'))
			 ->setPath($arr['path'])
			 ->setCreation((int)MiscService::get($arr, 'creation'));

		return true;
	}


	/**
	 * @param string $json
	 */
	public function fromJSON($json) {
		$this->fromArray(json_decode($json, true));
	}

}