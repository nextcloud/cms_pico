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

	/** @var string */
	private $userId;

	/** @var string */
	private $name;

	/** @var string */
	private $site;

	/** @var string */
	private $theme = 'default';

	/** @var int */
	private $type = self::TYPE_PUBLIC;

	/** @var array */
	private $options = [];

	/** @var string */
	private $path;

	/** @var int */
	private $creation;

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
	 * @param string $userId
	 *
	 * @return $this
	 */
	public function setUserId(string $userId): self
	{
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserId(): string
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
	private function fromArray(array $data): void
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
			->setCreation($creation);
	}
}
