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

namespace OCA\CMSPico\Service;

use OCA\CMSPico\AppInfo\Application;
use OCP\IConfig;
use OCP\IUserSession;

class ConfigService
{
	/** @var string */
	public const SYSTEM_TEMPLATES = 'system_templates';

	/** @var string */
	public const CUSTOM_TEMPLATES = 'custom_templates';

	/** @var string */
	public const SYSTEM_THEMES = 'system_themes';

	/** @var string */
	public const CUSTOM_THEMES = 'custom_themes';

	/** @var string */
	public const THEMES_ETAG = 'themes_etag';

	/** @var string */
	public const SYSTEM_PLUGINS = 'system_plugins';

	/** @var string */
	public const CUSTOM_PLUGINS = 'custom_plugins';

	/** @var string */
	public const PLUGINS_ETAG = 'plugins_etag';

	/** @var string */
	public const LIMIT_GROUPS = 'limit_groups';

	/** @var string */
	public const LINK_MODE = 'link_mode';

	/** @var array<string,mixed> */
	private $defaults = [
		self::SYSTEM_TEMPLATES => '',
		self::CUSTOM_TEMPLATES => '',
		self::SYSTEM_THEMES => '',
		self::CUSTOM_THEMES => '',
		self::THEMES_ETAG => '',
		self::SYSTEM_PLUGINS => '',
		self::CUSTOM_PLUGINS => '',
		self::PLUGINS_ETAG => '',
		self::LIMIT_GROUPS => '',
		self::LINK_MODE => WebsitesService::LINK_MODE_LONG,
	];

	/** @var IConfig */
	private $config;

	/** @var IUserSession */
	private $userSession;

	/**
	 * ConfigService constructor.
	 *
	 * @param IConfig      $config
	 * @param IUserSession $userSession
	 */
	public function __construct(IConfig $config, IUserSession $userSession)
	{
		$this->config = $config;
		$this->userSession = $userSession;
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getAppValue(string $key)
	{
		$defaultValue = $this->getDefaultValue($key);
		return $this->config->getAppValue(Application::APP_NAME, $key, $defaultValue);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setAppValue(string $key, $value): void
	{
		$this->config->setAppValue(Application::APP_NAME, $key, $value);
	}

	/**
	 * @param string $key
	 */
	public function deleteAppValue(string $key): void
	{
		$this->config->deleteAppValue(Application::APP_NAME, $key);
	}

	/**
	 * @param string      $key
	 * @param string|null $userId
	 *
	 * @return mixed
	 */
	public function getUserValue(string $key, string $userId = null)
	{
		$userId = $userId ?? $this->userSession->getUser()->getUID();
		$defaultValue = $this->getDefaultValue($key);
		return $this->config->getUserValue($userId, Application::APP_NAME, $key, $defaultValue);
	}

	/**
	 * @param string      $key
	 * @param mixed       $value
	 * @param string|null $userId
	 */
	public function setUserValue(string $key, $value, string $userId = null): void
	{
		$userId = $userId ?? $this->userSession->getUser()->getUID();
		$this->config->setUserValue($userId, Application::APP_NAME, $key, $value);
	}

	/**
	 * @param string      $key
	 * @param string|null $userId
	 */
	public function deleteUserValue(string $key, string $userId = null): void
	{
		$userId = $userId ?? $this->userSession->getUser()->getUID();
		$this->config->deleteUserValue($userId, Application::APP_NAME, $key);
	}

	/**
	 * @param string $key
	 * @param mixed  $defaultValue
	 *
	 * @return mixed
	 */
	public function getSystemValue(string $key, $defaultValue = '')
	{
		return $this->config->getSystemValue($key, $defaultValue);
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	private function getDefaultValue(string $key)
	{
		return $this->defaults[$key] ?? '';
	}
}
