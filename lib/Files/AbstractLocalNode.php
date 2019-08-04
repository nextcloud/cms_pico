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
use OCP\Files\AlreadyExistsException;
use OCP\Files\GenericFileException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

abstract class AbstractLocalNode extends AbstractNode implements NodeInterface
{
	/** @var string */
	protected $path;

	/** @var string */
	protected $basePath;

	/** @var LocalFolder */
	protected $parentFolder;

	/** @var int */
	protected $permissions;

	/** @var MiscService */
	private $miscService;

	/**
	 * AbstractLocalNode constructor.
	 *
	 * @param string $path
	 * @param string $basePath
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function __construct(string $path, string $basePath)
	{
		$this->miscService = \OC::$server->query(MiscService::class);

		$this->path = $this->normalizePath($path);
		$this->basePath = realpath($basePath ?: \OC::$SERVERROOT);

		if (!file_exists($this->getLocalPath())) {
			throw new NotFoundException();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function rename(string $name): NodeInterface
	{
		$this->assertValidFileName($name);

		$parentNode = $this->getParentNode();
		if ($parentNode->exists($name)) {
			throw new AlreadyExistsException();
		}
		if (!$parentNode->isCreatable()) {
			throw new NotPermittedException();
		}

		if (!rename($this->getLocalPath(), dirname($this->getLocalPath()) . '/' . $name)) {
			throw new GenericFileException();
		}

		$parentPath = dirname($this->path);
		$this->path = (($parentPath !== '/') ? $parentPath : '') . '/' . $name;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function copy(FolderInterface $targetPath, string $name = null): NodeInterface
	{
		if (($targetPath instanceof LocalFolder) && $this->isFile()) {
			if ($name !== null) {
				$this->assertValidFileName($name);
			}

			if ($targetPath->exists($this->getName())) {
				throw new AlreadyExistsException();
			}
			if (!$targetPath->isCreatable()) {
				throw new NotPermittedException();
			}

			if (!@copy($this->getLocalPath(), $targetPath->getLocalPath() . '/' . ($name ?: $this->getName()))) {
				throw new GenericFileException();
			}

			return new LocalFile($targetPath->getPath() . '/' . $this->getName(), $this->basePath);
		} else {
			return parent::copy($targetPath);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function move(FolderInterface $targetPath, string $name = null): NodeInterface
	{
		if (($targetPath instanceof LocalFolder) && $this->isFile()) {
			if ($name !== null) {
				$this->assertValidFileName($name);
			}

			if ($targetPath->exists($this->getName())) {
				throw new AlreadyExistsException();
			}
			if (!$targetPath->isCreatable()) {
				throw new NotPermittedException();
			}

			if (!@rename($this->getLocalPath(), $targetPath->getLocalPath() . '/' . ($name ?: $this->getName()))) {
				throw new GenericFileException();
			}

			return new LocalFile($targetPath->getPath() . '/' . $this->getName(), $targetPath->getBasePath());
		} else {
			return parent::move($targetPath);
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

		if (!@unlink($this->getLocalPath())) {
			throw new GenericFileException();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getBasePath(): string
	{
		return $this->basePath;
	}

	/**
	 * @return string
	 */
	public function getLocalPath(): string
	{
		return $this->basePath . (($this->path !== '/') ? '/' . $this->path : '');
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
		if ($this->path === '/') {
			throw new InvalidPathException();
		}

		return dirname($this->path);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParentNode(): FolderInterface
	{
		if ($this->parentFolder === null) {
			$this->parentFolder = new LocalFolder($this->getParent(), $this->basePath);
		}

		return $this->parentFolder;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isFile(): bool
	{
		return is_file($this->getLocalPath());
	}

	/**
	 * {@inheritDoc}
	 */
	public function isFolder(): bool
	{
		return is_dir($this->getLocalPath());
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPermissions(): int
	{
		if ($this->permissions === null) {
			$this->permissions = 0;

			if (is_readable($this->getLocalPath())) {
				$this->permissions |= Constants::PERMISSION_READ;
			}

			if (is_writable($this->getLocalPath())) {
				$this->permissions |= Constants::PERMISSION_UPDATE;

				if ($this->isFolder()) {
					$this->permissions |= Constants::PERMISSION_CREATE;
				}

				try {
					if (is_writable($this->basePath . $this->getParent())) {
						$this->permissions |= Constants::PERMISSION_DELETE;
					}
				} catch (InvalidPathException $e) {}
			}
		}

		return $this->permissions;
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 * @throws InvalidPathException
	 */
	protected function normalizePath(string $path): string
	{
		return '/' . $this->miscService->normalizePath($path);
	}
}
