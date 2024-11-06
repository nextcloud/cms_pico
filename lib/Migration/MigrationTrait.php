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

namespace OCA\CMSPico\Migration;

use OCA\CMSPico\AppInfo\Application;
use OCP\Migration\IOutput;
use Psr\Log\LoggerInterface;

trait MigrationTrait
{
	/** @var LoggerInterface */
	private $logger;

	/** @var IOutput */
	private $output;

	/**
	 * @param LoggerInterface $logger
	 */
	protected function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * @param IOutput $output
	 */
	protected function setOutput(IOutput $output): void
	{
		$this->output = $output;
	}

	/**
	 * @param string  $message
	 * @param mixed ...$arguments
	 */
	protected function logInfo(string $message, ...$arguments): void
	{
		if (!$this->logger || !$this->output) {
			throw new \LogicException('No logger or output instance set');
		}

		$message = sprintf($message, ...$arguments);
		$this->logger->info($message, [ 'app' => Application::APP_NAME ]);
		$this->output->info($message);
	}

	/**
	 * @param string $message
	 * @param mixed ...$arguments
	 */
	protected function logWarning(string $message, ...$arguments): void
	{
		if (!$this->logger || !$this->output) {
			throw new \LogicException('No logger or output instance set');
		}

		$message = sprintf($message, ...$arguments);
		$this->logger->warning($message, [ 'app' => Application::APP_NAME ]);
		$this->output->warning($message);
	}

	/**
	 * @param string $title
	 * @param array  $newItems
	 * @param array  $oldItems
	 * @param bool   $warnUpdates
	 */
	protected function logChanges(string $title, array $newItems, array $oldItems, bool $warnUpdates = false): void
	{
		if (!$this->logger || !$this->output) {
			throw new \LogicException('No logger or output instance set');
		}

		$addedItems = array_diff($newItems, $oldItems);
		foreach ($addedItems as $item) {
			$this->logInfo('Adding %s "%s"', $title, $item);
		}

		$updatedItems = array_intersect($newItems, $oldItems);
		if ($warnUpdates) {
			foreach ($updatedItems as $item) {
				$this->logWarning('Replacing %s "%s"', $title, $item);
			}
		} else {
			foreach ($updatedItems as $item) {
				$this->logInfo('Keeping %s "%s"', $title, $item);
			}
		}

		$removedItems = array_diff($oldItems, $newItems);
		foreach ($removedItems as $item) {
			$this->logWarning('Removing %s "%s"', $title, $item);
		}
	}
}
