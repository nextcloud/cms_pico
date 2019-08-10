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

use OCP\Files\File as OCFile;
use OCP\Files\Folder as OCFolder;
use OCP\Files\InvalidPathException;
use OCP\Files\Node as OCNode;
use OCP\Files\NotFoundException;

abstract class AbstractStorageNode extends AbstractNode implements NodeInterface
{
	/** @var OCNode */
	protected $node;

	/** @var string */
	protected $path;

	/** @var StorageFolder */
	protected $parentFolder;

	/**
	 * StorageNode constructor.
	 *
	 * @param OCNode      $node
	 * @param string|null $basePath
	 *
	 * @throws InvalidPathException
	 */
	public function __construct(OCNode $node, string $basePath = null)
	{
		parent::__construct();

		$this->node = $node;

		if ($basePath !== null) {
			$basePath = $this->normalizePath($basePath);
			$nodePath = $this->normalizePath($node->getPath());

			$path = (($basePath !== '/') ? $basePath : '') . '/' . basename($nodePath);
			if (!substr_compare($nodePath, $path, -strlen($path)) === 0) {
				throw new InvalidPathException();
			}

			$this->path = $path;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function rename(string $name): NodeInterface
	{
		$this->assertValidFileName($name);

		$parentPath = $this->getParentNode();
		if ($this->isFolder()) {
			/** @var StorageFolder $this */
			$target = $parentPath->newFolder($name);
			foreach ($this as $child) {
				$child->move($target);
			}
		} else {
			/** @var StorageFile $this */
			$target = $parentPath->newFile($name);
			$target->putContent($this->getContent());
		}

		$this->delete();
		return $target;
	}

	/**
	 * {@inheritDoc}
	 */
	public function copy(FolderInterface $targetPath, string $name = null): NodeInterface
	{
		if (($targetPath instanceof StorageFolder) && ($name === null)) {
			/** @var OCFolder $ocNode */
			$ocNode = $this->node->copy($targetPath->getOCNode()->getPath());
			return new StorageFolder($ocNode, $targetPath->getPath());
		} else {
			return parent::copy($targetPath, $name);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function move(FolderInterface $targetPath, string $name = null): NodeInterface
	{
		if (($targetPath instanceof StorageFolder) && ($name === null)) {
			/** @var OCFolder $ocNode */
			$ocNode = $this->node->move($targetPath->getOCNode()->getPath());
			return new StorageFolder($ocNode, $targetPath->getPath());
		} else {
			return parent::move($targetPath, $name);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete()
	{
		$this->node->delete();
	}

	/**
	 * @return OCNode
	 */
	public function getOCNode(): OCNode
	{
		return $this->node;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPath(): string
	{
		return $this->path ?: ($this->isFolder() ? '/' : '/' . $this->getName());
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocalPath(): string
	{
		$localPath = null;

		try {
			$storage = $this->node->getStorage();
			$internalPath = $this->node->getInternalPath();
			$localPath = $storage->getLocalFile($internalPath);
		} catch (\Exception $e) {}

		if ($localPath && file_exists($localPath)) {
			if ($this->isFolder() ? is_dir($localPath) : is_file($localPath)) {
				return $localPath;
			}

			throw new InvalidPathException();
		}

		throw new NotFoundException();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return $this->node->getName();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParent(): string
	{
		return $this->getParentNode()->getPath();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParentNode(): FolderInterface
	{
		if ($this->parentFolder === null) {
			if ($this->path === null) {
				throw new InvalidPathException();
			}

			try {
				$ocNode = $this->node->getParent();
			} catch (NotFoundException $e) {
				throw new InvalidPathException();
			}

			$this->parentFolder = new StorageFolder($ocNode, $this->path . '/../');
		}

		return $this->parentFolder;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isFile(): bool
	{
		return ($this->node instanceof OCFile);
	}

	/**
	 * {@inheritDoc}
	 */
	public function isFolder(): bool
	{
		return ($this->node instanceof OCFolder);
	}

	/**
	 * {@inheritDoc}
	 */
	public function isLocal(): bool
	{
		try {
			if ($this->getOCNode()->getStorage()->isLocal()) {
				return true;
			}
		} catch (\Exception $e) {}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPermissions(): int
	{
		try {
			return $this->node->getPermissions();
		} catch (\Exception $e) {
			return 0;
		}
	}
}
