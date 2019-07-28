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

use OCA\CMSPico\AppInfo\Application;
use OCP\Files\InvalidPathException;
use OCP\Security\ISecureRandom;

class MiscService
{
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
	 *
	 * @return string
	 * @throws InvalidPathException
	 */
	public function getRelativePath(string $path, string $basePath = null): string
	{
		if (!$basePath) {
			$basePath = \OC::$SERVERROOT;
		}

		$basePath = $this->normalizePath($basePath) . '/';
		$basePathLength = strlen($basePath);

		$path = $this->normalizePath($path);
		return (substr($path, 0, $basePathLength) === $basePath) ? substr($path, $basePathLength) : $path;
	}

	/**
	 * @param string $suffix
	 */
	public function getRandomFileName(string $suffix = ''): string
	{
		$randomChars = ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS;
		$random = \OC::$server->getSecureRandom()->generate(10, $randomChars);
		return 'tmp.' . $random . '_' . Application::APP_NAME . ($suffix ? '_' . $suffix : '');
	}
}
