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

	/** @var StorageFolder */
	protected $parentFolder;

	/**
	 * StorageNode constructor.
	 *
	 * @param OCNode $node
	 */
	public function __construct(OCNode $node)
	{
		$this->node = $node;
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
			foreach ($this->listing() as $child) {
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
			$ocNode = $this->node->copy($targetPath->getPath());
			return new StorageFolder($ocNode);
		} else {
			return parent::copy($targetPath);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function move(FolderInterface $targetPath, string $name = null): NodeInterface
	{
		if (($targetPath instanceof StorageFolder) && ($name === null)) {
			/** @var OCFolder $ocNode */
			$ocNode = $this->node->move($targetPath->getPath());
			return new StorageFolder($ocNode);
		} else {
			return parent::move($targetPath);
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
		return $this->node->getPath();
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
		return $this->node->getParent()->getPath();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParentNode(): FolderInterface
	{
		if ($this->parentFolder === null) {
			$this->parentFolder = new StorageFolder($this->node->getParent());
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
	public function getPermissions(): int
	{
		return $this->node->getPermissions();
	}

	/**
	 * {@inheritDoc}
	 */
	public function isReadable(): bool
	{
		return $this->node->isReadable();
	}

	/**
	 * {@inheritDoc}
	 */
	public function isUpdateable(): bool
	{
		return $this->node->isUpdateable();
	}

	/**
	 * {@inheritDoc}
	 */
	public function isDeletable(): bool
	{
		return $this->node->isDeletable();
	}
}
