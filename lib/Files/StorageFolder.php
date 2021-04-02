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

use OC\Files\Utils\Scanner;
use OC\ForbiddenException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\AlreadyExistsException;
use OCP\Files\Folder as OCFolder;
use OCP\Files\InvalidPathException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\ITempManager;

class StorageFolder extends AbstractStorageNode implements FolderInterface
{
	use FolderTrait;

	/** @var array<string,string> */
	private static $localPathCache = [];

	/** @var OCFolder */
	protected $node;

	/** @var ITempManager */
	private $tempManager;

	/** @var IDBConnection */
	private $connection;

	/** @var ILogger */
	private $logger;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var StorageFolder|null */
	protected $rootFolder;

	/**
	 * StorageFolder constructor.
	 *
	 * @param OCFolder    $folder
	 * @param string|null $parentPath
	 *
	 * @throws InvalidPathException
	 */
	public function __construct(OCFolder $folder, string $parentPath = null)
	{
		$this->tempManager = \OC::$server->getTempManager();
		$this->connection = \OC::$server->query(IDBConnection::class);
		$this->logger = \OC::$server->query(ILogger::class);
		$this->eventDispatcher = \OC::$server->query(IEventDispatcher::class);

		parent::__construct($folder, $parentPath);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocalPath(): string
	{
		if ($this->isLocal()) {
			return parent::getLocalPath();
		}

		$cachePath = $this->getOCNode()->getPath();
		if (!isset(self::$localPathCache[$cachePath])) {
			self::$localPathCache[$cachePath] = $this->tempManager->getTemporaryFolder();
		}

		return self::$localPathCache[$cachePath];
	}

	/**
	 * {@inheritDoc}
	 */
	public function listing(): array
	{
		return iterator_to_array($this->getGenerator());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getGenerator(): \Generator
	{
		$basePath = $this->getPath();
		foreach ($this->node->getDirectoryListing() as $node) {
			yield $this->repackNode($node, $basePath);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists(string $path): bool
	{
		$path = $this->normalizePath($this->getPath() . '/' . $path);
		return $this->getRootFolder()->getOCNode()->nodeExists($path);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $path): NodeInterface
	{
		$path = $this->normalizePath($this->getPath() . '/' . $path);
		$basePath = ($path !== '/') ? dirname($path) : null;
		return $this->repackNode($this->getRootFolder()->getOCNode()->get($path), $basePath);
	}

	/**
	 * {@inheritDoc}
	 */
	public function newFolder(string $path): FolderInterface
	{
		if ($this->exists($path)) {
			throw new AlreadyExistsException();
		}

		$path = $this->normalizePath($this->getPath() . '/' . $path);

		$name = basename($path);
		$parentPath = dirname($path);
		$basePath = ($path !== '/') ? $parentPath : null;

		/** @var StorageFolder $parentFolder */
		$parentFolder = $this->newFolderRecursive($parentPath);

		if (!$parentFolder->isCreatable()) {
			throw new NotPermittedException();
		}

		return new StorageFolder($parentFolder->getOCNode()->newFolder($name), $basePath);
	}

	/**
	 * {@inheritDoc}
	 */
	public function newFile(string $path): FileInterface
	{
		if ($this->exists($path)) {
			throw new AlreadyExistsException();
		}

		$path = $this->normalizePath($this->getPath() . '/' . $path);

		$name = basename($path);
		$parentPath = dirname($path);
		$basePath = ($path !== '/') ? $parentPath : null;

		/** @var StorageFolder $parentFolder */
		$parentFolder = $this->newFolderRecursive($parentPath);

		if (!$parentFolder->isCreatable()) {
			throw new NotPermittedException();
		}

		return new StorageFile($parentFolder->getOCNode()->newFile($name), $basePath);
	}

	/**
	 * {@inheritDoc}
	 */
	public function fakeRoot(): FolderInterface
	{
		return new StorageFolder($this->node);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sync(bool $recursive = FolderInterface::SYNC_RECURSIVE): void
	{
		$scanner = new Scanner(null, $this->connection, $this->eventDispatcher, $this->logger);

		try {
			$scanner->scan($this->node->getPath(), $recursive);
		} catch (ForbiddenException $e) {
			throw new NotPermittedException();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function isCreatable(): bool
	{
		return $this->node->isCreatable();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getRootFolder(): self
	{
		if ($this->getPath() === '/') {
			return $this;
		}

		if ($this->rootFolder === null) {
			$ocFolder = $this->node;
			for ($i = 0; $i < substr_count($this->getPath(), '/'); $i++) {
				$ocFolder = $ocFolder->getParent();
			}

			$this->rootFolder = new StorageFolder($ocFolder);
		}

		return $this->rootFolder;
	}
}
