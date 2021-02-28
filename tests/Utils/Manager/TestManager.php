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

namespace OCA\CMSPico\Tests\Utils\Manager;

class TestManager
{
	/** @var static[] */
	private static $instances;

	/** @var string */
	protected $testCaseName;

	/**
	 * @param string $testCaseName
	 *
	 * @return static
	 */
	public static function getInstance(string $testCaseName): self
	{
		if (!isset(self::$instances[$testCaseName][static::class])) {
			$manager = new static($testCaseName);
			$manager->setUp();

			self::$instances[$testCaseName][static::class] = $manager;
		}

		return self::$instances[$testCaseName][static::class];
	}

	public static function resetInstance(string $testCaseName): void
	{
		if (isset(self::$instances[$testCaseName][static::class])) {
			$manager = self::$instances[$testCaseName][static::class];
			$manager->tearDown();

			unset(self::$instances[$testCaseName][static::class]);
		}
	}

	public static function resetInstances(string $testCaseName): void
	{
		foreach (array_reverse(self::$instances[$testCaseName] ?? []) as $manager) {
			/** @var static $manager */
			$manager->tearDown();
		}

		unset(self::$instances[$testCaseName]);
	}

	private function __construct(string $testCaseName)
	{
		$this->testCaseName = $testCaseName;
	}

	public function setUp(): void
	{
		// nothing to do by default
	}

	public function tearDown(): void
	{
		// nothing to do by default
	}
}
