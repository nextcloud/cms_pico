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

use OCP\Files\GenericFileException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class LocalFile extends AbstractLocalNode implements FileInterface
{
	/**
	 * LocalFile constructor.
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

		if (!is_file($this->getLocalPath())) {
			throw new InvalidPathException();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(): void
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
		if (!$this->isReadable()) {
			throw new NotPermittedException();
		}

		if (($data = @file_get_contents($this->getLocalPath())) === false) {
			throw new GenericFileException();
		}

		return $data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function putContent(string $data): void
	{
		if (!$this->isUpdateable()) {
			throw new NotPermittedException();
		}

		if (@file_put_contents($this->getLocalPath(), $data) === false) {
			throw new GenericFileException();
		}
	}
}
