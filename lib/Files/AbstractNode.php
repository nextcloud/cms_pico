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

use OCA\CMSPico\Exceptions\UnknownFileTypeException;
use OCP\Constants;

abstract class AbstractNode implements NodeInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function copy(FolderInterface $targetPath)
	{
		if ($this->isFolder()) {
			/** @var FolderInterface $sourceFolder */
			$sourceFolder = $this;
			$targetFolder = $targetPath->newFolder($sourceFolder->getName());
			foreach ($sourceFolder->listing() as $sourceNode) {
				$sourceNode->copy($targetFolder);
			}
		} else {
			/** @var FileInterface $sourceFile */
			$sourceFile = $this;
			$targetFile = $targetPath->newFile($sourceFile->getName());
			$targetFile->putContent($sourceFile->getContent());
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function move(FolderInterface $targetPath)
	{
		if ($this->isFolder()) {
			/** @var FolderInterface $sourceFolder */
			$sourceFolder = $this;
			$targetFolder = $targetPath->newFolder($sourceFolder->getName());
			foreach ($sourceFolder->listing() as $sourceNode) {
				$sourceNode->move($targetFolder);
			}
		} else {
			/** @var FileInterface $sourceFile */
			$sourceFile = $this;
			$targetFile = $targetPath->newFile($sourceFile->getName());
			$targetFile->putContent($sourceFile->getContent());
		}

		$this->delete();
	}

	/**
	 * @return bool
	 */
	public function isFile(): bool
	{
		return ($this instanceof FileInterface);
	}

	/**
	 * @return bool
	 */
	public function isFolder(): bool
	{
		return ($this instanceof FolderInterface);
	}

	/**
	 * @return bool
	 */
	public function isReadable(): bool
	{
		return ($this->getPermissions() & Constants::PERMISSION_READ) === Constants::PERMISSION_READ;
	}

	/**
	 * @return bool
	 */
	public function isUpdateable(): bool
	{
		return ($this->getPermissions() & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE;
	}

	/**
	 * @return bool
	 */
	public function isDeletable(): bool
	{
		return ($this->getPermissions() & Constants::PERMISSION_DELETE) === Constants::PERMISSION_DELETE;
	}
}
