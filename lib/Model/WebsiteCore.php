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

class WebsiteCore implements \JsonSerializable
{
	/** @var int */
	public const TYPE_PUBLIC = 1;

	/** @var int */
	public const TYPE_PRIVATE = 2;

	/** @var int */
	private $id;

	/** @var string|null */
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
	 * @param array|null $data
	 */
	public function __construct(array $data = null)
	{
		if ($data !== null) {
			$this->fromArray($data);
		}
	}

	/**
	 * @param int $id
	 *
	 * @return $this
	 */
	public function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @param string|null $userId
	 *
	 * @return $this
	 */
	public function setUserId(?string $userId): self
	{
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getUserId(): ?string
	{
		return $this->userId;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setName(string $name): self
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $site
	 *
	 * @return $this
	 */
	public function setSite(string $site): self
	{
		$this->site = $site;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSite(): string
	{
		return $this->site;
	}

	/**
	 * @param string $theme
	 *
	 * @return $this
	 */
	public function setTheme(string $theme): self
	{
		$this->theme = $theme;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTheme(): string
	{
		return $this->theme;
	}

	/**
	 * @param int $type
	 *
	 * @return $this
	 */
	public function setType(int $type): self
	{
		if (!in_array($type, [ self::TYPE_PUBLIC, self::TYPE_PRIVATE ], true)) {
			throw new \UnexpectedValueException();
		}

		$this->type = $type;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return $this
	 */
	public function setOption(string $key, $value): self
	{
		if ($value === null) {
			unset($this->options[$key]);
			return $this;
		}

		$this->options[$key] = $value;
		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getOption(string $key)
	{
		return $this->options[$key] ?? null;
	}

	/**
	 * @param array|string|null $options
	 *
	 * @return $this
	 */
	public function setOptions($options): self
	{
		if (is_string($options)) {
			$options = json_decode($options, true);
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
	public function getOptions(): array
	{
		return $this->options;
	}

	/**
	 * @return string
	 */
	public function getOptionsJSON(): string
	{
		return json_encode($this->options);
	}

	/**
	 * @param string $path
	 *
	 * @return $this
	 */
	public function setPath(string $path): self
	{
		$this->path = $path ? rtrim($path, '/') . '/' : '';
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @param int $creation
	 *
	 * @return $this
	 */
	public function setCreation(int $creation): self
	{
		$this->creation = $creation;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getCreation(): int
	{
		return $this->creation;
	}

	/**
	 * @param string $source
	 *
	 * @return $this
	 */
	public function setTemplateSource(string $source): self
	{
		$this->templateSource = $source;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateSource(): string
	{
		return $this->templateSource;
	}

	/**
	 * @param string $page
	 *
	 * @return $this
	 */
	public function setPage(string $page): self
	{
		$this->page = $page;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPage(): string
	{
		return $this->page;
	}

	/**
	 * @param string $viewer
	 *
	 * @return $this
	 */
	public function setViewer(string $viewer): self
	{
		$this->viewer = $viewer;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getViewer(): string
	{
		return $this->viewer;
	}

	/**
	 * @param bool $proxyRequest
	 *
	 * @return $this
	 */
	public function setProxyRequest(bool $proxyRequest): self
	{
		$this->proxyRequest = $proxyRequest;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getProxyRequest(): bool
	{
		return $this->proxyRequest;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'user_id' => $this->getUserId(),
			'name' => $this->getName(),
			'site' => $this->getSite(),
			'theme' => $this->getTheme(),
			'type' => $this->getType(),
			'options' => $this->getOptions(),
			'path' => $this->getPath(),
			'creation' => $this->getCreation(),
			'template' => $this->getTemplateSource(),
			'page' => $this->getPage(),
		];
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->getData();
	}

	/**
	 * @param array $data
	 *
	 * @throws \UnexpectedValueException
	 */
	public function fromArray(array $data): void
	{
		if (!isset($data['user_id']) || !isset($data['name']) || !isset($data['site']) || !isset($data['path'])) {
			throw new \UnexpectedValueException();
		}

		$options = [];
		if (!empty($data['options'])) {
			$options = is_array($data['options']) ? $data['options'] : json_decode($data['options'], true);
		}

		$creation = 0;
		if (!empty($data['creation'])) {
			$creation = is_numeric($data['creation']) ? (int) $data['creation'] : strtotime($data['creation']);
		}

		$this->setId(isset($data['id']) ? (int) $data['id'] : 0)
			->setUserId($data['user_id'])
			->setName($data['name'])
			->setSite($data['site'])
			->setTheme($data['theme'] ?? 'default')
			->setType(isset($data['type']) ? (int) $data['type'] : self::TYPE_PUBLIC)
			->setOptions($options)
			->setPath($data['path'])
			->setCreation($creation)
			->setTemplateSource($data['template'] ?? '')
			->setPage($data['page'] ?? '')
			->setViewer($data['viewer'] ?? '')
			->setProxyRequest(!empty($data['proxyRequest']));
	}
}
