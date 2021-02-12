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

namespace OCA\CMSPico\Tests\Utils\Manager;

use OC\AppFramework\DependencyInjection\DIContainer;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Tests\TestCase;
use OCP\AppFramework\QueryException;

class TestServicesManager extends TestManager
{
	/** @var object[] */
	protected static $originalServices = [];

	/** @var int[] */
	protected static $serviceUsage = [];

	/** @var object[][] */
	protected static $services = [];

	public function setUp(): void
	{
		if ($this->testCaseName === TestCase::class) {
			$this->resetAppContainer();
		}
	}

	public function tearDown(): void
	{
		foreach (self::$services[static::class] ?? [] as $name => $_) {
			unset(self::$services[static::class][$name]);

			if (self::$serviceUsage[$name] <= 1) {
				$this->restoreOriginalService($name);
			}
		}
	}

	public function overwriteService(string $name, $newService): self
	{
		if (!isset(self::$originalServices[$name])) {
			self::$originalServices[$name] = \OC::$server->query($name);
			self::$serviceUsage[$name] = 1;

			$container = \OC::$server->getAppContainerForService($name) ?? \OC::$server;
			$container->registerService($name, static function () use ($name) {
				$isTestCase = false;
				foreach (debug_backtrace(0, 0) as $frame) {
					/** @var string $testCaseName */
					$testCaseName = $frame['class'] ?? null;
					if ($testCaseName && is_a($testCaseName, TestCase::class, true)) {
						if (isset(self::$services[$testCaseName][$name])) {
							return self::$services[$testCaseName][$name];
						}

						$isTestCase = true;
					}
				}

				if ($isTestCase) {
					return self::$services[TestCase::class][$name] ?? self::$originalServices[$name];
				}

				throw new QueryException('Could not resolve mocked service "%s": Invalid test case context');
			}, false);
		} elseif (!isset(self::$services[$this->testCaseName][$name])) {
			self::$serviceUsage[$name]++;
		}

		if (!is_object($newService)) {
			if (!is_string($newService) || !class_exists($newService)) {
				throw new \RuntimeException(sprintf('Invalid service class "%s"', $newService));
			}

			$newServiceParameters = self::getServiceArguments($newService);
			$newService = new $newService(...$newServiceParameters);
		}

		self::$services[$this->testCaseName][$name] = $newService;
		return $this;
	}

	public function restoreService(string $name): self
	{
		if (isset(self::$services[$this->testCaseName][$name])) {
			unset(self::$services[$this->testCaseName][$name]);

			if (self::$serviceUsage[$name] <= 1) {
				$this->restoreOriginalService($name);
			}
		}

		return $this;
	}

	protected function restoreOriginalService(string $name): void
	{
		if (isset(self::$originalServices[$name])) {
			$originalService = self::$originalServices[$name];
			unset(self::$originalServices[$name]);
			unset(self::$serviceUsage[$name]);

			array_walk(self::$services, static function (?array &$services) use ($name) {
				unset($services[$name]);
				$services = $services ?: null;
			});

			$container = \OC::$server->getAppContainerForService($name) ?? \OC::$server;
			$container->registerService($name, static function () use ($originalService) {
				return $originalService;
			});
		}
	}

	protected function resetAppContainer(): void
	{
		$appContainer = null;

		try {
			$appContainer = \OC::$server->getRegisteredAppContainer(Application::APP_NAME);
		} catch (QueryException $e) {
			// there's no app container yet, thus there's nothing to unload
		}

		if ($appContainer !== null) {
			$appName = $appContainer->query('appName');
			$urlParams = $appContainer->query('urlParams');

			// reset app container to unload all cached services
			new DIContainer($appName, $urlParams);
		}
	}

	public static function getServiceArguments(string $className): array
	{
		$reflectionClass = new \ReflectionClass($className);
		if (!$reflectionClass->isInstantiable()) {
			$errorTemplate = 'Could not build parameters for service "%s": Class is not instantiable';
			throw new QueryException(sprintf($errorTemplate, $className));
		}

		$arguments = [];
		if ($reflectionClass->getConstructor() !== null) {
			foreach ($reflectionClass->getConstructor()->getParameters() as $parameter) {
				$parameterType = $parameter->getType();
				if (($parameterType instanceof \ReflectionNamedType) && !$parameterType->isBuiltin()) {
					try {
						$arguments[] = \OC::$server->query($parameterType->getName());
						continue;
					} catch (QueryException $e) {
						// ignore exception, try again using parameter name
					}
				}

				try {
					$arguments[] = \OC::$server->query($parameter->getName(), false);
					continue;
				} catch (QueryException $e) {
					// fallback to default value when possible
					if ($parameter->isDefaultValueAvailable()) {
						$arguments[] = $parameter->getDefaultValue();
						continue;
					}

					throw $e;
				}
			}
		}

		return $arguments;
	}
}
