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
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Pico;
use OCA\CMSPico\Service\MiscService;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;
use Symfony\Component\Yaml\Parser as YamlParser;
use function OCA\CMSPico\t;

class Theme implements \JsonSerializable
{
	/** @var int */
	public const TYPE_SYSTEM = 1;

	/** @var int */
	public const TYPE_CUSTOM = 2;

	/** @var int[] */
	public const THEME_API_VERSIONS = [
		Pico::API_VERSION_0,
		Pico::API_VERSION_1,
		Pico::API_VERSION_2,
		Pico::API_VERSION_3,
		Pico::API_VERSION_4,
	];

	/** @var MiscService */
	private $miscService;

	/** @var FolderInterface */
	private $folder;

	/** @var int */
	private $type;

	/** @var bool|null */
	private $compat;

	/** @var ThemeNotCompatibleException|null */
	private $compatException;

	/**
	 * Theme constructor.
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
		} catch (ThemeNotCompatibleException $e) {
			return false;
		}
	}

	/**
	 * @throws ThemeNotCompatibleException
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
				$this->getFolder()->getFile('index.twig');
			} catch (InvalidPathException | NotFoundException $e) {
				throw new ThemeNotCompatibleException(
					$this->getName(),
					t('Incompatible theme: Twig template "{file}" not found.'),
					[ 'file' => $this->getName() . '/index.twig' ]
				);
			}

			try {
				$themeConfigFile = $this->getFolder()->getFile('pico-theme.yml');
				$themeConfigYaml = $themeConfigFile->getContent();

				$themeConfig = (new YamlParser())->parse($themeConfigYaml);
				$themeConfig = is_array($themeConfig) ? $themeConfig : [];
			} catch (InvalidPathException | NotFoundException | NotPermittedException | YamlParseException $e) {
				$themeConfig = [];
			}

			$apiVersion = Pico::API_VERSION_0;
			if (isset($themeConfig['api_version'])) {
				if (is_int($themeConfig['api_version']) || preg_match('/^[0-9]+$/', $themeConfig['api_version'])) {
					$apiVersion = (int) $themeConfig['api_version'];
				}
			}

			if (!in_array($apiVersion, static::THEME_API_VERSIONS, true)) {
				throw new ThemeNotCompatibleException(
					$this->getName(),
					t('Incompatible theme: Themes for Pico CMS for Nextcloud must use one of the API versions '
							. '{compatApiVersions}, but this theme uses API version {apiVersion}.'),
					[ 'compatApiVersions' => implode(', ', static::THEME_API_VERSIONS), 'apiVersion' => $apiVersion ]
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
