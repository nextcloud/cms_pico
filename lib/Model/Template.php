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

namespace OCA\CMSPico\Model;

use OCA\CMSPico\Exceptions\TemplateNotCompatibleException;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Service\MiscService;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use function OCA\CMSPico\t;

class Template implements \JsonSerializable
{
	/** @var int */
	public const TYPE_SYSTEM = 1;

	/** @var int */
	public const TYPE_CUSTOM = 2;

	/** @var MiscService */
	private $miscService;

	/** @var FolderInterface */
	private $folder;

	/** @var int */
	private $type;

	/** @var bool|null */
	private $compat;

	/** @var TemplateNotCompatibleException|null */
	private $compatException;

	/**
	 * Template constructor.
	 *
	 * @param FolderInterface $folder
	 * @param int             $type
	 */
	public function __construct(FolderInterface $folder, int $type = self::TYPE_SYSTEM)
	{
		$this->miscService = \OC::$server->query(MiscService::class);

		$this->folder = $folder;
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->folder->getName();
	}

	/**
	 * @return FolderInterface
	 */
	public function getFolder(): FolderInterface
	{
		return $this->folder;
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * @return bool
	 */
	public function isCompatible(): bool
	{
		if ($this->compat !== null) {
			return $this->compat;
		}

		try {
			$this->checkCompatibility();
			return true;
		} catch (TemplateNotCompatibleException $e) {
			return false;
		}
	}

	/**
	 * @throws TemplateNotCompatibleException
	 */
	public function checkCompatibility(): void
	{
		if ($this->compat === false) {
			throw $this->compatException;
		} elseif ($this->compat) {
			return;
		}

		try {
			try {
				$this->folder->getFolder('assets');
			} catch (InvalidPathException | NotFoundException $e) {
				throw new TemplateNotCompatibleException(
					$this->getName(),
					t('Incompatible template: Required directory "{file}" not found.'),
					[ 'file' => $this->getName() . '/assets/' ]
				);
			}

			try {
				$this->folder->getFolder('content');
			} catch (InvalidPathException | NotFoundException $e) {
				throw new TemplateNotCompatibleException(
					$this->getName(),
					t('Incompatible template: Required directory "{file}" not found.'),
					[ 'file' => $this->getName() . '/content/' ]
				);
			}

			$this->compat = true;
			$this->compatException = null;
		} catch (TemplateNotCompatibleException $e) {
			$this->compat = false;
			$this->compatException = $e;

			throw $e;
		}
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$data = [
			'name' => $this->getName(),
			'type' => $this->getType(),
			'compat' => $this->isCompatible(),
		];

		if (!$this->isCompatible() && ($this->compatException !== null)) {
			$data['compatReason'] = $this->compatException->getRawReason();
			$data['compatReasonData'] = $this->compatException->getRawReasonData();
		}

		return $data;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
