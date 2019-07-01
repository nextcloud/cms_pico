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

use OCP\Constants;
use OCP\Files\AlreadyExistsException;
use OCP\Files\GenericFileException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class LocalFolder extends AbstractLocalNode implements FolderInterface
{
	/** @var LocalFolder */
	private $baseFolder;

	/**
	 * LocalFolder constructor.
	 *
	 * @param string $path
	 * @param string $basePath
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function __construct(string $path, string $basePath)
	{
		parent::__construct($path, $basePath);

		if (!is_dir($this->getLocalPath())) {
			throw new NotFoundException();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete()
	{
		if (!$this->isDeletable()) {
			throw new NotPermittedException();
		}

		foreach ($this->listing() as $node) {
			$node->delete();
		}

		if (!@rmdir($this->getLocalPath())) {
			$files = @scandir($this->getLocalPath());
			if (($files === false) || ($files === [ '.', '..' ])) {
				throw new GenericFileException();
			} else {
				throw new NotPermittedException();
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function listing(): array
	{
		if (!$this->isReadable()) {
			throw new NotPermittedException();
		}

		$files = @scandir($this->getLocalPath());
		if ($files === false) {
			throw new GenericFileException();
		}

		$nodes = [];
		foreach ($files as $file) {
			if (($file === '.') || ($file === '..')) {
				continue;
			}

			$path = (($this->path !== '/') ? $this->path . '/' : '/') . $file;
			$node = $this->createNode($path);
			if ($node !== null) {
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists(string $path): bool
	{
		$path = $this->normalizePath($this->path . '/' . $path);

		return file_exists($this->basePath . $path);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $path): NodeInterface
	{
		if (!$this->exists($path)) {
			throw new NotFoundException();
		}

		$path = $this->normalizePath($this->path . '/' . $path);

		$node = $this->createNode($path);
		if ($node !== null) {
			return $node;
		}

		throw new NotFoundException();
	}

	/**
	 * {@inheritDoc}
	 */
	public function newFolder(string $path): FolderInterface
	{
		if ($this->exists($path)) {
			throw new AlreadyExistsException();
		}

		$path = $this->normalizePath($this->path . '/' . $path);

		$parentPath = dirname($path);
		if ($parentPath !== '/') {
			if (!$this->getBaseFolder()->exists($parentPath)) {
				$this->getBaseFolder()->newFolder($parentPath);
			} elseif (!$this->getBaseFolder()->get($parentPath)->isFolder()) {
				throw new AlreadyExistsException();
			}
		}

		if (!@mkdir($this->basePath . '/' . $path)) {
			throw new GenericFileException();
		}

		return new LocalFolder($path, $this->basePath);
	}

	/**
	 * {@inheritDoc}
	 */
	public function newFile(string $path): FileInterface
	{
		if ($this->exists($path)) {
			throw new AlreadyExistsException();
		}

		$path = $this->normalizePath($this->path . '/' . $path);

		$parentPath = dirname($path);
		if ($parentPath !== '/') {
			if (!$this->getBaseFolder()->exists($parentPath)) {
				$this->getBaseFolder()->newFolder($parentPath);
			} elseif (!$this->getBaseFolder()->get($parentPath)->isFolder()) {
				throw new AlreadyExistsException();
			}
		}

		if (!@touch($this->basePath . '/' . $path)) {
			throw new GenericFileException();
		}

		return new LocalFile($path, $this->basePath);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sync(bool $recursive = self::SYNC_RECURSIVE)
	{
		// nothing to do
	}

	/**
	 * {@inheritDoc}
	 */
	public function isCreatable(): bool
	{
		return ($this->getPermissions() & Constants::PERMISSION_CREATE) === Constants::PERMISSION_CREATE;
	}

	/**
	 * @param string $path
	 *
	 * @return AbstractLocalNode|null
	 */
	private function createNode(string $path)
	{
		try {
			if ($path === '/') {
				return new LocalFolder('/', $this->basePath);
			} elseif (is_file($this->basePath . '/' . $path)) {
				return new LocalFile($path, $this->basePath);
			} elseif (is_dir($this->basePath . '/' . $path)) {
				return new LocalFolder($path, $this->basePath);
			}
		} catch (NotFoundException $e) {}

		return null;
	}

	/**
	 * @return LocalFolder
	 */
	private function getBaseFolder(): self
	{
		if ($this->baseFolder === null) {
			$this->baseFolder = new LocalFolder('/', $this->basePath);
		}

		return $this->baseFolder;
	}
}
