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

class ConfigService
{
	/** @var string */
	const SYSTEM_TEMPLATES = 'system_templates';

	/** @var string */
	const CUSTOM_TEMPLATES = 'custom_templates';

	/** @var string */
	const SYSTEM_THEMES = 'system_themes';

	/** @var string */
	const CUSTOM_THEMES = 'custom_themes';

	/** @var string */
	const THEMES_ETAG = 'themes_etag';

	/** @var string */
	const SYSTEM_PLUGINS = 'system_plugins';

	/** @var string */
	const CUSTOM_PLUGINS = 'custom_plugins';

	/** @var string */
	const PLUGINS_ETAG = 'plugins_etag';

	/** @var string */
	const LIMIT_GROUPS = 'limit_groups';

	/** @var string */
	const LINK_MODE = 'link_mode';

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

	/** @var string */
	private $userId;

	/**
	 * ConfigService constructor.
	 *
	 * @param IConfig     $config
	 * @param string      $userId
	 */
	public function __construct(IConfig $config, $userId)
	{
		$this->config = $config;
		$this->userId = $userId;
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
	public function setAppValue(string $key, $value)
	{
		$this->config->setAppValue(Application::APP_NAME, $key, $value);
	}

	/**
	 * @param string $key
	 */
	public function deleteAppValue(string $key)
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
		$defaultValue = $this->getDefaultValue($key);
		return $this->config->getUserValue($userId ?? $this->userId, Application::APP_NAME, $key, $defaultValue);
	}

	/**
	 * @param string      $key
	 * @param mixed       $value
	 * @param string|null $userId
	 */
	public function setUserValue(string $key, $value, string $userId = null)
	{
		$this->config->setUserValue($userId ?? $this->userId, Application::APP_NAME, $key, $value);
	}

	/**
	 * @param string      $key
	 * @param string|null $userId
	 */
	public function deleteUserValue(string $key, string $userId = null)
	{
		$this->config->deleteUserValue($userId ?? $this->userId, Application::APP_NAME, $key);
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
