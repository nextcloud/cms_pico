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

namespace OCA\CMSPico\Tests;

use OCA\CMSPico\Tests\Utils\Manager\TestConfigManager;
use OCA\CMSPico\Tests\Utils\Manager\TestExtensionManager;
use OCA\CMSPico\Tests\Utils\Manager\TestManager;
use OCA\CMSPico\Tests\Utils\Manager\TestServicesManager;
use PhrozenByte\PHPUnitArrayAsserts\ArrayAssertsTrait;
use PhrozenByte\PHPUnitThrowableAsserts\ThrowableAssertsTrait;

class TestCase extends \PHPUnit\Framework\TestCase
{
	use ThrowableAssertsTrait;
	use ArrayAssertsTrait;

	public static function setUpBeforeClass(): void
	{
		$db = \OC::$server->getDatabaseConnection();
		$db->beginTransaction();

		TestServicesManager::getInstance(self::class);
		TestConfigManager::getInstance(self::class);
		TestExtensionManager::getInstance(self::class);

		parent::setUpBeforeClass();
	}

	public static function tearDownAfterClass(): void
	{
		parent::tearDownAfterClass();

		TestManager::resetInstances(static::class);

		$db = \OC::$server->getDatabaseConnection();
		$db->rollBack();
	}
}
