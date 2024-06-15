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

namespace OCA\CMSPico\Files\Glob;

use OCA\CMSPico\Files\FileInterface;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\NodeInterface;
use OCP\Files\GenericFileException;
use OCP\Files\NotPermittedException;

class GlobIterator implements \Iterator
{
	/** @var FolderInterface */
	private $folder;

	/** @var GlobPattern */
	private $pattern;

	/** @var FolderInterface[] */
	private $folders;

	/** @var FolderInterface */
	private $current;

	/** @var int */
	private $depth;

	/** @var FileInterface */
	private $currentValue;

	/** @var int */
	private $currentKey;

	/**
	 * GlobIterator constructor.
	 *
	 * @param FolderInterface $folder
	 * @param string          $pattern
	 */
	public function __construct(FolderInterface $folder, string $pattern)
	{
		$this->folder = $folder;
		$this->pattern = new GlobPattern($pattern);

		$this->init();
	}

	/**
	 * @return void
	 */
	private function init(): void
	{
		$this->folders = [ $this->folder ];
		$this->current = $this->folder;
		$this->depth = 0;

		$this->currentValue = null;
		$this->currentKey = -1;
	}

	/**
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function rewind(): void
	{
		$this->init();

		$this->current->rewind();
	}

	/**
	 * @return void
	 */
	public function next(): void
	{
		$this->current->next();
	}

	/**
	 * @return bool
	 */
	public function valid(): bool
	{
		do {
			if ($this->current->valid()) {
				/** @var NodeInterface $file */
				$file = $this->current->current();

				if (!$this->pattern->compare($this->depth, $file->getName())) {
					$this->next();
					continue;
				}

				if ($file->isFolder()) {
					/** @var FolderInterface $file */
					$this->descend($file);
					continue;
				}

				/** @var FileInterface $file */
				$this->set($file);
				return true;
			} elseif ($this->depth > 0) {
				$this->ascend();
				continue;
			}

			return false;
		} while (true);
	}

	/**
	 * @return FileInterface
	 */
	public function current(): FileInterface
	{
		return $this->currentValue;
	}

	/**
	 * @return int
	 */
	public function key(): int
	{
		return $this->currentKey;
	}

	/**
	 * @param FolderInterface $folder
	 */
	private function descend(FolderInterface $folder): void
	{
		$this->folders[] = $folder;
		$this->depth++;

		$this->current = $this->folders[$this->depth];
		$this->current->rewind();
	}

	/**
	 * @return void
	 */
	private function ascend(): void
	{
		array_pop($this->folders);
		$this->depth--;

		$this->current = $this->folders[$this->depth];
		$this->current->next();
	}

	/**
	 * @param FileInterface $file
	 */
	private function set(FileInterface $file): void
	{
		$this->currentValue = $file;
		$this->currentKey++;
	}
}
