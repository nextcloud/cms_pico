<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2021, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
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

namespace OCA\CMSPico\Tests\Utils\Mocks\Service;

use OCA\CMSPico\Tests\TestCase;

class ConfigService extends \OCA\CMSPico\Service\ConfigService
{
	/** @var mixed[][] */
	protected $appValueDefaults;

	/** @var mixed[][] */
	protected $appValues;

	/** @var mixed[][] */
	protected $systemValueDefaults;

	public function overwriteAppValue(string $testCaseName, string $key, string $value): void
	{
		$this->appValueDefaults[$testCaseName][$key] = $value;
	}

	public function restoreAppValue(string $testCaseName, string $key): void
	{
		unset($this->appValueDefaults[$testCaseName][$key]);
	}

	public function resetAppValues(string $testCaseName): void
	{
		unset($this->appValueDefaults[$testCaseName]);
		unset($this->appValues[$testCaseName]);
	}

	public function getAppValue(string $key): string
	{
		$testCaseName = $this->getTestCaseName();
		return $this->appValues[$testCaseName][$key]
			?? $this->appValueDefaults[$testCaseName][$key]
			?? $this->appValueDefaults[TestCase::class][$key]
			?? parent::getAppValue($key);
	}

	public function setAppValue(string $key, string $value): void
	{
		$testCaseName = $this->getTestCaseName();
		$this->appValues[$testCaseName][$key] = $value;
	}

	public function deleteAppValue(string $key): void
	{
		$testCaseName = $this->getTestCaseName();
		unset($this->appValues[$testCaseName][$key]);
	}

	public function overwriteSystemValue(string $testCaseName, string $key, $value): void
	{
		$this->systemValueDefaults[$testCaseName][$key] = $value;
	}

	public function restoreSystemValue(string $testCaseName, string $key): void
	{
		unset($this->systemValueDefaults[$testCaseName][$key]);
	}

	public function resetSystemValues(string $testCaseName): void
	{
		unset($this->systemValueDefaults[$testCaseName]);
	}

	public function getSystemValue(string $key, $defaultValue = '')
	{
		$testCaseName = $this->getTestCaseName();
		return $this->systemValueDefaults[$testCaseName][$key]
			?? parent::getSystemValue($key, $defaultValue);
	}

	private function getTestCaseName(): string
	{
		foreach (debug_backtrace(0, 0) as $frame) {
			/** @var string $testCaseName */
			$testCaseName = $frame['class'] ?? null;
			if ($testCaseName && is_a($testCaseName, TestCase::class, true)) {
				return $testCaseName;
			}
		}

		throw new \RuntimeException('Could not resolve mocked test config: Invalid test case context');
	}
}
