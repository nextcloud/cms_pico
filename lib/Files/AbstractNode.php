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

abstract class AbstractNode implements NodeInterface
{
	/** @var MiscService */
	protected $miscService;

	/**
	 * AbstractNode constructor.
	 */
	public function __construct()
	{
		$this->miscService = \OC::$server->query(MiscService::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function copy(FolderInterface $targetPath, string $name = null): NodeInterface
	{
		if ($name !== null) {
			$this->assertValidFileName($name);
		}

		if ($this->isFolder()) {
			/** @var FolderInterface $this */
			$target = $targetPath->newFolder($name ?? $this->getName());
			foreach ($this as $child) {
				$child->copy($target);
			}
		} else {
			/** @var FileInterface $this */
			$target = $targetPath->newFile($name ?? $this->getName());
			$target->putContent($this->getContent());
		}

		return $target;
	}

	/**
	 * {@inheritDoc}
	 */
	public function move(FolderInterface $targetPath, string $name = null): NodeInterface
	{
		if ($name !== null) {
			$this->assertValidFileName($name);
		}

		if ($this->isFolder()) {
			/** @var FolderInterface $this */
			$target = $targetPath->newFolder($name ?? $this->getName());
			foreach ($this as $child) {
				$child->move($target);
			}
		} else {
			/** @var FileInterface $this */
			$target = $targetPath->newFile($name ?? $this->getName());
			$target->putContent($this->getContent());
		}

		$this->delete();
		return $target;
	}

	/**
	 * {@inheritDoc}
	 */
	public function truncate(): void
	{
		if ($this->isFolder()) {
			/** @var FolderInterface $this */
			foreach ($this as $child) {
				$child->delete();
			}
		} else {
			/** @var FileInterface $this */
			$this->putContent('');
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function isFile(): bool
	{
		return ($this instanceof FileInterface);
	}

	/**
	 * {@inheritDoc}
	 */
	public function isFolder(): bool
	{
		return ($this instanceof FolderInterface);
	}

	/**
	 * {@inheritDoc}
	 */
	public function isReadable(): bool
	{
		return ($this->getPermissions() & Constants::PERMISSION_READ) === Constants::PERMISSION_READ;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isUpdateable(): bool
	{
		return ($this->getPermissions() & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isDeletable(): bool
	{
		return ($this->getPermissions() & Constants::PERMISSION_DELETE) === Constants::PERMISSION_DELETE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function __toString(): string
	{
		return $this->getName();
	}

	/**
	 * @param string $name
	 *
	 * @throws InvalidPathException
	 */
	protected function assertValidFileName(string $name): void
	{
		if (in_array($name, [ '', '.', '..' ], true)) {
			throw new InvalidPathException();
		}
		if ((strpos($name, '/') !== false) || (strpos($name, '\\') !== false)) {
			throw new InvalidPathException();
		}
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
