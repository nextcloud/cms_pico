<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2017, Maxence Lange (<maxence@artificial-owl.com>)
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

namespace OCA\CMSPico\Model;

use OCA\CMSPico\Files\AbstractNode;
use OCA\CMSPico\Files\FileInterface;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\NodeInterface;
use OCP\Constants;
use OCP\Files\GenericFileException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class TemplateFile extends AbstractNode implements FileInterface
{
	/** @var FileInterface */
	private $file;

	/** @var bool */
	private $isBinary;

	/** @var array<string,string> */
	private $data = [];

	/**
	 * TemplateFile constructor.
	 *
	 * @param FileInterface $file
	 */
	public function __construct(FileInterface $file)
	{
		parent::__construct();

		$this->file = $file;
	}

	/**
	 * {@inheritDoc}
	 */
	public function rename(string $name): NodeInterface
	{
		throw new NotPermittedException();
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(): void
	{
		// nothing to do
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPath(): string
	{
		return $this->file->getPath();
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
		return $this->file->getName();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParent(): string
	{
		return $this->file->getParent();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParentNode(): FolderInterface
	{
		throw new InvalidPathException();
	}

	/**
	 * {@inheritDoc}
	 */
	public function isLocal(): bool
	{
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPermissions(): int
	{
		return Constants::PERMISSION_READ | Constants::PERMISSION_DELETE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExtension(): string
	{
		return $this->file->getExtension();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getContent(): string
	{
		if ($this->isBinary() || empty($this->data)) {
			return $this->file->getContent();
		}

		return str_replace(array_keys($this->data), $this->data, $this->file->getContent());
	}

	/**
	 * {@inheritDoc}
	 */
	public function putContent(string $data): void
	{
		throw new NotPermittedException();
	}

	/**
	 * @return bool
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function isBinary(): bool
	{
		if ($this->isBinary === null) {
			$this->isBinary = $this->miscService->isBinaryFile($this->file);
		}

		return $this->isBinary;
	}

	/**
	 * @param array<string,string> $data
	 */
	public function setTemplateData(array $data): void
	{
		$this->data = [];
		foreach ($data as $key => $value) {
			$this->data['%%' . $key . '%%'] = $value;
		}
	}
}
