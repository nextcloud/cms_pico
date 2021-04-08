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

use OCP\Files\AlreadyExistsException;
use OCP\Files\GenericFileException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

trait FolderTrait
{
	/** @var \Generator */
	private $generator;

	/**
	 * @param string $path
	 *
	 * @return FolderInterface
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws GenericFileException
	 */
	public function getFolder(string $path): FolderInterface
	{
		/** @var FolderInterface $folder */
		$folder = $this->get($path);
		if (!$folder->isFolder()) {
			throw new InvalidPathException();
		}

		return $folder;
	}

	/**
	 * @param string $path
	 *
	 * @return FileInterface
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws GenericFileException
	 */
	public function getFile(string $path): FileInterface
	{
		/** @var FileInterface $file */
		$file = $this->get($path);
		if (!$file->isFile()) {
			throw new InvalidPathException();
		}

		return $file;
	}

	/**
	 * @param string $fullPath
	 *
	 * @return FolderInterface
	 * @throws AlreadyExistsException
	 * @throws InvalidPathException
	 * @throws NotPermittedException
	 */
	protected function newFolderRecursive(string $fullPath): FolderInterface
	{
		if ($fullPath !== '/') {
			if (!$this->getRootFolder()->exists($fullPath)) {
				return $this->getRootFolder()->newFolder($fullPath);
			} else {
				/** @var FolderInterface $parentFolder */
				$parentFolder = $this->getRootFolder()->get($fullPath);
				if (!$parentFolder->isFolder()) {
					throw new AlreadyExistsException();
				}
				return $parentFolder;
			}
		} else {
			return $this->getRootFolder();
		}
	}

	/**
	 * @return FolderInterface
	 * @throws InvalidPathException
	 */
	abstract protected function getRootFolder(): FolderInterface;

	/**
	 * @return \Generator
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	abstract protected function getGenerator(): \Generator;

	/**
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function rewind(): void
	{
		$this->generator = $this->getGenerator();
	}

	/**
	 * @return void
	 */
	public function next(): void
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
