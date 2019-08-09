<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
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

namespace OCA\CMSPico\Files;

trait FolderIteratorTrait
{
	/** @var \Generator */
	private $generator;

	/**
	 * @return \Generator
	 */
	abstract protected function getGenerator(): \Generator;

	/**
	 * @return void
	 */
	public function rewind()
	{
		$this->generator = $this->getGenerator();
	}

	/**
	 * @return void
	 */
	public function next()
	{
		$this->generator->next();
	}

	/**
	 * @return bool
	 */
	public function valid(): bool
	{
		return $this->generator->valid();
	}

	/**
	 * @return NodeInterface
	 */
	public function current(): NodeInterface
	{
		return $this->generator->current();
	}

	/**
	 * @return int
	 */
	public function key(): int
	{
		return $this->generator->key();
	}

	/**
	 * @return bool
	 */
	public function hasChildren(): bool
	{
		return ($this->current() instanceof FolderInterface);
	}

	/**
	 * @return \RecursiveIterator
	 */
	public function getChildren(): \RecursiveIterator
	{
		$node = $this->current();
		if ($node instanceof FolderInterface) {
			/** @var FolderInterface $node */
			return $node;
		}

		throw new \InvalidArgumentException();
	}
}
