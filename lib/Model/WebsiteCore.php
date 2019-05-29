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

namespace OCA\CMSPico\Model;

use OCA\CMSPico\Service\MiscService;

class WebsiteCore implements \JsonSerializable
{
	/** @var int */
	const TYPE_PUBLIC = 1;

	/** @var int */
	const TYPE_PRIVATE = 2;

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

	/** @var bool */
	private $proxyRequest;

	/** @var string */
	private $templateSource;

	/**
	 * WebsiteCore constructor.
	 *
	 * @param array|string $data
	 */
	public function __construct($data = '')
	{
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
	public function setId($id): self
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setName($name): self
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $theme
	 *
	 * @return $this
	 */
	public function setTheme($theme): self
	{
		$this->theme = $theme;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * @param string $userId
	 *
	 * @return $this
	 */
	public function setUserId($userId): self
	{
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param string $site
	 *
	 * @return $this
	 */
	public function setSite($site): self
	{
		$this->site = $site;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSite()
	{
		return $this->site;
	}

	/**
	 * @param int $type
	 *
	 * @return $this
	 */
	public function setType($type): self
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setOption($key, $value): self
	{
		$this->options[$key] = $value;
		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function getOption($key)
	{
		return MiscService::get($this->options, $key, '');
	}

	/**
	 * @param array|string $options
	 *
	 * @return $this
	 */
	public function setOptions($options): self
	{
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
	public function getOptions($json = false)
	{
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
	public function setPath($path): self
	{
		$this->path = MiscService::endSlash($path);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param string $page
	 *
	 * @return $this
	 */
	public function setPage($page): self
	{
		$this->page = $page;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @param int $creation
	 *
	 * @return $this
	 */
	public function setCreation($creation): self
	{
		$this->creation = $creation;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getCreation()
	{
		return $this->creation;
	}

	/**
	 * @param string $viewer
	 *
	 * @return $this
	 */
	public function setViewer($viewer): self
	{
		$this->viewer = $viewer;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getViewer()
	{
		return $this->viewer;
	}

	/**
	 * @param bool $proxyRequest
	 *
	 * @return $this
	 */
	public function setProxyRequest($proxyRequest): self
	{
		$this->proxyRequest = $proxyRequest;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getProxyRequest()
	{
		return $this->proxyRequest;
	}

	/**
	 * @param string $source
	 *
	 * @return $this
	 */
	public function setTemplateSource($source): self
	{
		$this->templateSource = $source;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateSource()
	{
		return $this->templateSource;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'user_id' => $this->getUserId(),
			'site' => $this->getSite(),
			'page' => $this->getPage(),
			'theme' => $this->getTheme(),
			'type' => $this->getType(),
			'options' => $this->getOptions(),
			'path' => $this->getPath(),
			'creation' => $this->getCreation(),
		];
	}

	/**
	 * @param array $arr
	 *
	 * @return bool
	 */
	public function fromArray($arr): bool
	{
		if (!is_array($arr)) {
			return false;
		}

		MiscService::mustContains($arr, [ 'name', 'user_id', 'site', 'type', 'path' ]);

		$this->setId((int)MiscService::get($arr, 'id'))
			->setName($arr['name'])
			->setUserId($arr['user_id'])
			->setSite($arr['site'])
			->setPage(MiscService::get($arr, 'page'))
			->setTheme(MiscService::get($arr, 'theme', 'default'))
			->setType($arr['type'])
			->setOptions(MiscService::get($arr, 'options'))
			->setPath($arr['path'])
			->setCreation((int)MiscService::get($arr, 'creation'));

		return true;
	}

	/**
	 * @param string $json
	 *
	 * @return bool
	 */
	public function fromJSON($json): bool
	{
		return $this->fromArray(json_decode($json, true));
	}
}
