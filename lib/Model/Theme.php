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

use OCA\CMSPico\Exceptions\ThemeNotCompatibleException;
use OCA\CMSPico\Files\LocalFolder;

class Theme implements \JsonSerializable
{
	/** @var int */
	const THEME_TYPE_SYSTEM = 1;

	/** @var int */
	const THEME_TYPE_CUSTOM = 2;

	/** @var LocalFolder */
	private $folder;

	/** @var int */
	private $type;

	/** @var bool|null */
	private $compat;

	/** @var ThemeNotCompatibleException|null */
	private $compatException;

	/**
	 * Plugin constructor.
	 *
	 * @param LocalFolder $folder
	 * @param int         $type
	 */
	public function __construct(LocalFolder $folder, int $type = self::THEME_TYPE_SYSTEM)
	{
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
	 * @return LocalFolder
	 */
	public function getFolder(): LocalFolder
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
		} catch (ThemeNotCompatibleException $e) {
			return false;
		}
	}

	/**
	 * @throws ThemeNotCompatibleException
	 */
	public function checkCompatibility()
	{
		if ($this->compat === false) {
			throw $this->compatException;
		} elseif ($this->compat) {
			return;
		}

		try {
			if (!is_file($this->getFolder()->getLocalPath() . '/index.twig')) {
				throw new ThemeNotCompatibleException(
					$this->getName(),
					'Incompatible theme: Twig template "{file}" not found.',
					[ 'file' => $this->getName() . '/index.twig' ]
				);
			}

			$this->compat = true;
			$this->compatException = null;
		} catch (ThemeNotCompatibleException $e) {
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

		if (!$this->isCompatible()) {
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
