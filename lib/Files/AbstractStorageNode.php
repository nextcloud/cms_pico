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

use OCP\Files\Folder as OCFolder;
use OCP\Files\Node as OCNode;
use OCP\Files\File as OCFile;

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
	public function copy(FolderInterface $targetPath)
	{
		if ($targetPath instanceof StorageFolder) {
			$this->node->copy($targetPath->getPath());
		} else {
			parent::copy($targetPath);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function move(FolderInterface $targetPath)
	{
		if ($targetPath instanceof StorageFolder) {
			$this->node->move($targetPath->getPath());
		} else {
			parent::move($targetPath);
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
