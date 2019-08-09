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

interface FolderInterface extends NodeInterface, \RecursiveIterator
{
	/** @var bool */
	const SYNC_SHALLOW = false;

	/** @var bool */
	const SYNC_RECURSIVE = true;

	/**
	 * @return NodeInterface[]
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function listing(): array;

	/**
	 * @param string $path
	 *
	 * @return bool
	 * @throws InvalidPathException
	 * @throws GenericFileException
	 */
	public function exists(string $path): bool;

	/**
	 * @param string $path
	 *
	 * @return NodeInterface
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws GenericFileException
	 */
	public function get(string $path): NodeInterface;

	/**
	 * @param string $path
	 *
	 * @return FolderInterface
	 * @throws InvalidPathException
	 * @throws AlreadyExistsException
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function newFolder(string $path): FolderInterface;

	/**
	 * @param string $path
	 *
	 * @return FileInterface
	 * @throws InvalidPathException
	 * @throws AlreadyExistsException
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function newFile(string $path): FileInterface;

	/**
	 * @param bool $recursive
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function sync(bool $recursive = self::SYNC_RECURSIVE);

	/**
	 * @return bool
	 */
	public function isCreatable(): bool;
}
