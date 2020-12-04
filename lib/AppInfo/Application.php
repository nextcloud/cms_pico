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

declare(strict_types=1);

namespace OCA\CMSPico\AppInfo;

use OCA\CMSPico\Listener\ExternalStorageBackendEventListener;
use OCA\CMSPico\Listener\GroupDeletedEventListener;
use OCA\CMSPico\Listener\UserDeletedEventListener;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\User\Events\UserDeletedEvent;

class Application extends App
{
	/** @var string */
	public const APP_NAME = 'cms_pico';

	/**
	 * @param array $params
	 */
	public function __construct(array $params = [])
	{
		parent::__construct(self::APP_NAME, $params);
	}

	/**
	 * Registers event listeners.
	 */
	public function registerEventListener(): void
	{
		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = \OC::$server->query(IEventDispatcher::class);
		$eventDispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedEventListener::class);
		$eventDispatcher->addServiceListener(GroupDeletedEvent::class, GroupDeletedEventListener::class);

		$eventDispatcher->addServiceListener(
			'OCA\\Files_External::loadAdditionalBackends',
			ExternalStorageBackendEventListener::class
		);
	}

	/**
	 * Returns the absolute path to this app.
	 *
	 * @return string
	 */
	public static function getAppPath(): string
	{
		try {
			/** @var IAppManager $appManager */
			$appManager = \OC::$server->getAppManager();
			return $appManager->getAppPath(self::APP_NAME);
		} catch (AppPathNotFoundException $e) {
			return '';
		}
	}

	/**
	 * Returns the absolute web path to this app.
	 *
	 * @return string
	 */
	public static function getAppWebPath(): string
	{
		try {
			/** @var IAppManager $appManager */
			$appManager = \OC::$server->getAppManager();
			return $appManager->getAppWebPath(self::APP_NAME);
		} catch (AppPathNotFoundException $e) {
			return '';
		}
	}

	/**
	 * Returns the installed version of this app.
	 *
	 * @return string
	 */
	public static function getAppVersion(): string
	{
		try {
			/** @var IAppManager $appManager */
			$appManager = \OC::$server->getAppManager();
			return $appManager->getAppVersion(self::APP_NAME);
		} catch (AppPathNotFoundException $e) {
			return '';
		}
	}
}
