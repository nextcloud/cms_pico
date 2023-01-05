<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2022, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
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
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class StorageScanner
{
	/** @var Scanner */
	private $scanner;

	/**
	 * @param IDBConnection    $connection
	 * @param IEventDispatcher $eventDispatcher
	 */
	public function __construct(IDBConnection $connection, IEventDispatcher $eventDispatcher)
	{
		$this->connection = \OC::$server->query(IDBConnection::class);
		$this->eventDispatcher = \OC::$server->query(IEventDispatcher::class);

		/** @var LoggerInterface $logger */
		$logger = \OC::$server->query(LoggerInterface::class);

		$this->scanner = new Scanner(null, $connection, $eventDispatcher, $logger);
	}

	/**
	 * @param string $path
	 * @param bool   $recursive
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function scan(string $path, bool $recursive = FolderInterface::SYNC_RECURSIVE): void
	{
		try {
			$this->scanner->scan($path, $recursive);
		} catch (ForbiddenException $e) {
			throw new NotPermittedException();
		}
	}
}
