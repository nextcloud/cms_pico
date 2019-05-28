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
use OCA\CMSPico\Exceptions\MissingKeyInArrayException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\InvalidPathException;
use OCP\ILogger;
use OCP\Util;

class MiscService {

	const ALPHA = 'abcdefghijklmnopqrstuvwxyz';
	const ALPHA_NUMERIC = 'abcdefghijklmnopqrstuvwxyz0123456789';
	const ALPHA_NUMERIC_SCORES = 'abcdefghijklmnopqrstuvwxyz0123456789_-';

	/** @var ILogger */
	private $logger;

	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}


	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public static function endSlash($path) {
		if ($path === '') {
			return '';
		}

		if (substr($path, -1, 1) !== '/') {
			$path .= '/';
		}

		return $path;
	}


	/**
	 * @param $arr
	 * @param $k
	 *
	 * @param string $default
	 *
	 * @return array|string|integer
	 */
	public static function get($arr, $k, $default = '') {
		if (!key_exists($k, $arr)) {
			return $default;
		}

		return $arr[$k];
	}


	public static function mustContains($data, $arr) {
		if (!is_array($arr)) {
			$arr = [$arr];
		}

		foreach ($arr as $k) {
			if (!key_exists($k, $data)) {
				throw new MissingKeyInArrayException();
			}
		}
	}


	public static function checkChars($line, $chars) {
		for ($i = 0; $i < strlen($line); $i++) {
			if (strpos($chars, substr($line, $i, 1)) === false) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array $data
	 *
	 * @return DataResponse
	 */
	public function fail($data) {
		$this->logger->log(2, $data['message'] ?? '', [ 'app'   => Application::APP_NAME ]);

		return new DataResponse(
			array_merge($data, array('status' => 0)),
			Http::STATUS_NON_AUTHORATIVE_INFORMATION
		);
	}


	/**
	 * @param array $data
	 *
	 * @return DataResponse
	 */
	public function success($data) {
		return new DataResponse(
			array_merge($data, array('status' => 1)),
			Http::STATUS_CREATED
		);
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
}
