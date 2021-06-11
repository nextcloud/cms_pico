<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2020, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
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
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class DummyPluginFile extends AbstractNode implements FileInterface
{
	/** @var string */
	private $pluginName;

	/** @var FileInterface */
	private $file;

	/**
	 * DummyPluginFile constructor.
	 *
	 * @param string        $pluginName
	 * @param FileInterface $file
	 *
	 * @throws InvalidPathException
	 */
	public function __construct(string $pluginName, FileInterface $file)
	{
		parent::__construct();

		$this->file = $file;
		$this->rename($pluginName . '.' . $this->getExtension());
	}

	/**
	 * {@inheritDoc}
	 */
	public function rename(string $name): NodeInterface
	{
		$this->assertValidFileName($name);

		$extension = '';
		if (($extensionPos = strrpos($name, '.')) !== false) {
			$extension = substr($name, $extensionPos + 1);
			$name = substr($name, 0, $extensionPos);
		}

		if ($extension !== $this->getExtension()) {
			throw new InvalidPathException();
		}
		if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name)) {
			throw new InvalidPathException();
		}

		$this->pluginName = $name;
		return $this;
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
		return '/' . $this->getName();
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
		return $this->pluginName . '.' . $this->getExtension();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParent(): string
	{
		return '/';
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
		return 'php';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getContent(): string
	{
		return preg_replace(
			'/^class DummyPlugin(?= |$)/m',
			'class ' . $this->pluginName,
			$this->file->getContent()
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function putContent(string $data): void
	{
		throw new NotPermittedException();
	}
}
