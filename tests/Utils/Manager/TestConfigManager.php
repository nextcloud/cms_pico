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

use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\WebsitesService;
use OCA\CMSPico\Tests\TestCase;
use OCA\CMSPico\Tests\Utils\Mocks\Service\ConfigService as MockedConfigService;

class TestConfigManager extends TestManager
{
	/** @var MockedConfigService */
	protected static $configService;

	public function setUp(): void
	{
		if ($this->testCaseName === TestCase::class) {
			$this->initConfigService();
		}
	}

	public function tearDown(): void
	{
		$this->getConfigService()->resetSystemValues($this->testCaseName);
		$this->getConfigService()->resetAppValues($this->testCaseName);
	}

	public function overwriteAppValue(string $key, $value): self
	{
		$this->getConfigService()->overwriteAppValue($this->testCaseName, $key, $value);
		return $this;
	}

	public function restoreAppValue(string $key): self
	{
		$this->getConfigService()->restoreAppValue($this->testCaseName, $key);
		return $this;
	}

	public function overwriteSystemValue(string $key, $value): self
	{
		$this->getConfigService()->overwriteSystemValue($this->testCaseName, $key, $value);
		return $this;
	}

	public function restoreSystemValue(string $key): self
	{
		$this->getConfigService()->restoreSystemValue($this->testCaseName, $key);
		return $this;
	}

	protected function getConfigService(): MockedConfigService
	{
		if (self::$configService === null) {
			$errorMessage = 'Unable to use test config manager: Config service has not been initialized yet';
			throw new \RuntimeException($errorMessage);
		}

		return self::$configService;
	}

	protected function initConfigService(): void
	{
		$configServiceParameters = TestServicesManager::getServiceArguments(MockedConfigService::class);
		self::$configService = new MockedConfigService(...$configServiceParameters);

		TestServicesManager::getInstance(TestCase::class)
			->overwriteService(ConfigService::class, self::$configService);

		self::$configService->overwriteAppValue(
			TestCase::class,
			ConfigService::LIMIT_GROUPS,
			''
		);

		self::$configService->overwriteAppValue(
			TestCase::class,
			ConfigService::LINK_MODE,
			(string) WebsitesService::LINK_MODE_LONG
		);
	}
}
