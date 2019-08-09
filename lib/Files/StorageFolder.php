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
use OCP\Files\File as OCFile;
use OCP\Files\Folder as OCFolder;
use OCP\Files\Node as OCNode;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use OCP\ILogger;

class StorageFolder extends AbstractStorageNode implements FolderInterface
{
	use FolderIteratorTrait;

	/** @var OCFolder */
	protected $node;

	/** @var IDBConnection */
	private $connection;

	/** @var ILogger */
	private $logger;

	/**
	 * StorageFolder constructor.
	 *
	 * @param OCFolder $folder
	 */
	public function __construct(OCFolder $folder)
	{
		$this->connection = \OC::$server->query(IDBConnection::class);
		$this->logger = \OC::$server->query(ILogger::class);

		parent::__construct($folder);
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
		foreach ($this->node->getDirectoryListing() as $node) {
			yield $this->repackNode($node);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists(string $path): bool
	{
		return $this->node->nodeExists($path);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $path): NodeInterface
	{
		return $this->repackNode($this->node->get($path));
	}

	/**
	 * {@inheritDoc}
	 */
	public function newFolder(string $path): FolderInterface
	{
		return new StorageFolder($this->node->newFolder($path));
	}

	/**
	 * {@inheritDoc}
	 */
	public function newFile(string $path): FileInterface
	{
		return new StorageFile($this->node->newFile($path));
	}

	/**
	 * {@inheritDoc}
	 */
	public function sync(bool $recursive = FolderInterface::SYNC_RECURSIVE)
	{
		$scanner = new Scanner(null, $this->connection, $this->logger);

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
	 * @param OCNode $node
	 *
	 * @return AbstractStorageNode
	 */
	private function repackNode(OCNode $node): AbstractStorageNode
	{
		if ($node instanceof OCFile) {
			return new StorageFile($node);
		} elseif ($node instanceof OCFolder) {
			return new StorageFolder($node);
		} else {
			throw new \UnexpectedValueException();
		}
	}
}
