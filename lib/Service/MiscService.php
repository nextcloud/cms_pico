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

namespace OCA\CMSPico\Service;

use OCA\CMSPico\Files\FileInterface;
use OCP\Files\GenericFileException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotPermittedException;
use OCP\Security\ISecureRandom;

class MiscService
{
	/** @var string[] */
	private $textFileMagic;

	/** @var string[] */
	private $binaryFileMagic;

	/**
	 * MiscService constructor.
	 */
	public function __construct()
	{
		$this->textFileMagic = [
			hex2bin('EFBBBF'),
			hex2bin('0000FEFF'),
			hex2bin('FFFE0000'),
			hex2bin('FEFF'),
			hex2bin('FFFE')
		];

		$this->binaryFileMagic = [
			'%PDF',
			hex2bin('89') . 'PNG'
		];
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 * @throws InvalidPathException
	 */
	public function normalizePath(string $path): string
	{
		$path = str_replace('\\', '/', $path);
		$pathParts = explode('/', $path);

		$resultParts = [];
		foreach ($pathParts as $pathPart) {
			if (($pathPart === '') || ($pathPart === '.')) {
				continue;
			} elseif ($pathPart === '..') {
				if (!$resultParts) {
					throw new InvalidPathException();
				}

				array_pop($resultParts);
				continue;
			}

			$resultParts[] = $pathPart;
		}

		return implode('/', $resultParts);
	}

	/**
	 * @param string      $path
	 * @param string|null $basePath
	 * @param string|null $fileExtension
	 *
	 * @return string
	 * @throws InvalidPathException
	 */
	public function getRelativePath(string $path, string $basePath = null, string $fileExtension = null): string
	{
		if (!$basePath) {
			$basePath = \OC::$SERVERROOT;
		}

		$basePath = $this->normalizePath($basePath);
		$basePathLength = strlen($basePath);

		$path = $this->normalizePath($path);

		if ($path === $basePath) {
			$relativePath = '';
		} elseif (substr($path, 0, $basePathLength + 1) === $basePath . '/') {
			$relativePath = substr($path, $basePathLength + 1);
		} else {
			throw new InvalidPathException();
		}

		if ($fileExtension) {
			$fileName = basename($relativePath);
			$fileExtensionPos = strrpos($fileName, '.');
			if (($fileExtensionPos === false) || (substr($fileName, $fileExtensionPos) !== $fileExtension)) {
				throw new InvalidPathException();
			}

			return substr($relativePath, 0, strlen($relativePath) - strlen($fileExtension));
		}

		return $relativePath;
	}

	/**
	 * @param int    $length
	 * @param string $prefix
	 * @param string $suffix
	 *
	 * @return string
	 */
	public function getRandom(int $length = 10, string $prefix = '', string $suffix = ''): string
	{
		$randomChars = ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS;
		$random = \OC::$server->getSecureRandom()->generate($length, $randomChars);
		return ($prefix ? $prefix . '.' : '') . $random . ($suffix ? '.' . $suffix : '');
	}

	/**
	 * @param FileInterface $file
	 *
	 * @return bool
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function isBinaryFile(FileInterface $file): bool
	{
		$buffer = false;

		try {
			$buffer = file_get_contents($file->getLocalPath(), false, null, 0, 1024);
		} catch (\Exception $e) {}

		if ($buffer === false) {
			$buffer = substr($file->getContent(), 0, 1024);
		}

		if ($buffer === '') {
			return false;
		}

		foreach ($this->textFileMagic as $textFileMagic) {
			if (substr_compare($buffer, $textFileMagic, 0, strlen($textFileMagic)) === 0) {
				return false;
			}
		}

		foreach ($this->binaryFileMagic as $binaryFileMagic) {
			if (substr_compare($buffer, $binaryFileMagic, 0, strlen($binaryFileMagic)) === 0) {
				return true;
			}
		}

		return (strpos($buffer, "\0") !== false);
	}
}
