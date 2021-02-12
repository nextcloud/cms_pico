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

use OCA\CMSPico\Service\PluginsService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Tests\TestCase;
use OCA\CMSPico\Tests\Utils\Mocks\Service\PluginsService as MockedPluginsService;
use OCA\CMSPico\Tests\Utils\Mocks\Service\ThemesService as MockedThemesService;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\IsType;
use PhrozenByte\PHPUnitArrayAsserts\Assert as ArrayAssert;

class TestExtensionManager extends TestManager
{
	/** @var bool */
	protected $initialized = false;

	/** @var array{name: string, type: int, compat: bool}[] */
	protected $systemItems;

	/** @var array{name: string, type: int, compat: bool}[] */
	protected $customItems;

	/** @var string[] */
	protected $newItems;

	public function setUp(): void
	{
		if ($this->testCaseName === TestCase::class) {
			$this->initExtensionServices();
		}
	}

	public function tearDown(): void
	{
		$this->resetItems();
	}

	public function initItems(array $data): self
	{
		$this->initialized = true;
		$this->systemItems = $data['systemItems'] ?? [];
		$this->customItems = $data['customItems'] ?? [];
		$this->newItems = $data['newItems'] ?? [];

		return $this;
	}

	public function resetItems(): self
	{
		$this->initialized = false;
		$this->systemItems = [];
		$this->customItems = [];
		$this->newItems = [];

		return $this;
	}

	public function addSystemItem(string $name, array $data): self
	{
		if (!$this->initialized) {
			$errorTemplate = 'Unable to add system item "%s": Items have not been initialized yet';
			throw new \LogicException(sprintf($errorTemplate, $name));
		}

		$this->systemItems[$name] = $data;
		return $this;
	}

	public function removeSystemItem(string $name): self
	{
		if (!$this->initialized) {
			$errorTemplate = 'Unable to remove system item "%s": Items have not been initialized yet';
			throw new \LogicException(sprintf($errorTemplate, $name));
		}

		unset($this->systemItems[$name]);
		return $this;
	}

	public function addCustomItem(string $name, array $data): self
	{
		if (!$this->initialized) {
			$errorTemplate = 'Unable to add custom item "%s": Items have not been initialized yet';
			throw new \LogicException(sprintf($errorTemplate, $name));
		}

		$this->customItems[$name] = $data;
		return $this;
	}

	public function removeCustomItem(string $name): self
	{
		if (!$this->initialized) {
			$errorTemplate = 'Unable to remove custom item "%s": Items have not been initialized yet';
			throw new \LogicException(sprintf($errorTemplate, $name));
		}

		unset($this->customItems[$name]);
		return $this;
	}

	public function addNewItem(string $name): self
	{
		if (!$this->initialized) {
			$errorTemplate = 'Unable to add new item "%s": Items have not been initialized yet';
			throw new \LogicException(sprintf($errorTemplate, $name));
		}

		if (!in_array($name, $this->newItems)) {
			$this->newItems[] = $name;
		}

		return $this;
	}

	public function removeNewItem(string $name): self
	{
		if (!$this->initialized) {
			$errorTemplate = 'Unable to remove new item "%s": Items have not been initialized yet';
			throw new \LogicException(sprintf($errorTemplate, $name));
		}

		$this->newItems = array_filter(
			$this->newItems,
			static function (string $newItemName) use ($name): bool {
				return ($newItemName !== $name);
			}
		);

		return $this;
	}

	public function assertValidItems($data): self
	{
		Assert::assertThat($data, ArrayAssert::associativeArray([
			'systemItems' => Assert::isType(IsType::TYPE_ARRAY),
			'customItems' => Assert::isType(IsType::TYPE_ARRAY),
			'newItems' => Assert::isType(IsType::TYPE_ARRAY),
		]));

		$itemConstraint = ArrayAssert::associativeArray([
			'name' => Assert::isType(IsType::TYPE_STRING),
			'type' => Assert::isType(IsType::TYPE_INT),
			'compat' => Assert::isType(IsType::TYPE_BOOL),
		]);

		$systemItems = $data['systemItems'] ?? [];
		foreach ($systemItems as $systemItem) {
			Assert::assertThat($systemItem, $itemConstraint);
		}

		$customItems = $data['customItems'] ?? [];
		foreach ($customItems as $customItem) {
			Assert::assertThat($customItem, $itemConstraint);
		}

		$newItems = $data['newItems'] ?? [];
		foreach ($newItems as $newItem) {
			Assert::assertIsString($newItem);
		}

		return $this;
	}

	public function assertMatchingItems($data): self
	{
		if (!$this->initialized) {
			throw new \LogicException('Unable to assert expected items: Items have not been initialized yet');
		}

		$this->assertValidItems($data);

		$systemItems = $data['systemItems'] ?? [];
		Assert::assertCount(count($this->systemItems), $systemItems);
		ArrayAssert::assertAssociativeArray($this->systemItems, $systemItems, false, false);

		$customItems = $data['customItems'] ?? [];
		Assert::assertCount(count($this->customItems), $customItems);
		ArrayAssert::assertAssociativeArray($this->customItems, $customItems, false, false);

		$newItems = $data['newItems'] ?? [];
		Assert::assertCount(count($this->newItems), $newItems);
		ArrayAssert::assertAssociativeArray($this->newItems, $newItems, false, false);

		return $this;
	}

	protected function initExtensionServices(): void
	{
		TestServicesManager::getInstance(TestCase::class)
			->overwriteService(PluginsService::class, MockedPluginsService::class)
			->overwriteService(ThemesService::class, MockedThemesService::class);
	}
}
