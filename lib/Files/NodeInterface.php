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
use OCP\Files\NotPermittedException;

interface NodeInterface
{
	/**
	 * @param FolderInterface $targetPath
	 *
	 * @return NodeInterface
	 * @throws AlreadyExistsException
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function copy(FolderInterface $targetPath): NodeInterface;

	/**
	 * @param FolderInterface $targetPath
	 *
	 * @return NodeInterface
	 * @throws AlreadyExistsException
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function move(FolderInterface $targetPath): NodeInterface;

	/**
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function empty();

	/**
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function delete();

	/**
	 * @return string
	 */
	public function getPath(): string;

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return string
	 * @throws InvalidPathException
	 */
	public function getParent(): string;

	/**
	 * @return FolderInterface
	 * @throws InvalidPathException
	 */
	public function getParentNode(): FolderInterface;

	/**
	 * @return bool
	 */
	public function isFile(): bool;

	/**
	 * @return bool
	 */
	public function isFolder(): bool;

	/**
	 * @return int
	 */
	public function getPermissions(): int;

	/**
	 * @return bool
	 */
	public function isReadable(): bool;

	/**
	 * @return bool
	 */
	public function isUpdateable(): bool;

	/**
	 * @return bool
	 */
	public function isDeletable(): bool;
}
