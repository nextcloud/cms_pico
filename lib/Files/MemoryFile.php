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

use OCA\CMSPico\Service\MiscService;
use OCP\Constants;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;

class MemoryFile extends AbstractNode implements FileInterface
{
	/** @var string */
	private $path;

	/** @var string */
	private $data;

	/**
	 * AbstractMemoryNode constructor.
	 *
	 * @param string $path
	 * @param string $data
	 *
	 * @throws InvalidPathException
	 */
	public function __construct(string $path, string $data)
	{
		/** @var MiscService $miscService */
		$miscService = \OC::$server->query(MiscService::class);

		$this->path = '/' . $miscService->normalizePath($path);
		$this->data = $data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function rename(string $name): NodeInterface
	{
		$this->assertValidFileName($name);

		$parentPath = dirname($this->path);
		$this->path = (($parentPath !== '/') ? $parentPath : '') . '/' . $name;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExtension(): string
	{
		$pos = strrpos($this->getName(), '.');
		return ($pos !== false) ? substr($this->getName(), $pos + 1) : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getContent(): string
	{
		return $this->data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function putContent(string $data)
	{
		$this->data = $data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete()
	{
		// nothing to do
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocalPath(): string
	{
		throw new NotFoundException();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return basename($this->path);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParent(): string
	{
		return dirname($this->path);
	}

	/**
	 * MemoryFile doesn't support folders, this method always throws a {@see InvalidPathException}
	 *
	 * {@inheritDoc}
	 */
	public function getParentNode(): FolderInterface
	{
		throw new InvalidPathException();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPermissions(): int
	{
		return Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE;
	}
}
