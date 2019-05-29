<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2017, Maxence Lange (<maxence@artificial-owl.com>)
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
	const CUSTOM_TEMPLATES = 'custom_templates';

	/** @var string */
	const CUSTOM_THEMES = 'custom_themes';

	/** @var array<string,string> */
	private $defaults = [
		self::CUSTOM_THEMES => '',
		self::CUSTOM_TEMPLATES => '',
	];

	/** @var IConfig */
	private $config;

	/** @var string */
	private $userId;

	/** @var MiscService */
	private $miscService;

	/**
	 * ConfigService constructor.
	 *
	 * @param IConfig     $config
	 * @param string      $userId
	 * @param MiscService $miscService
	 */
	public function __construct(IConfig $config, $userId, MiscService $miscService)
	{
		$this->config = $config;
		$this->userId = $userId;
		$this->miscService = $miscService;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue(string $key): string
	{
		$defaultValue = $this->getDefaultValue($key);
		return $this->config->getAppValue(Application::APP_NAME, $key, $defaultValue);
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function setAppValue(string $key, string $value)
	{
		$this->config->setAppValue(Application::APP_NAME, $key, $value);
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function deleteAppValue(string $key): string
	{
		return $this->config->deleteAppValue(Application::APP_NAME, $key);
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function getUserValue(string $key): string
	{
		$defaultValue = $this->getDefaultValue($key);
		return $this->config->getUserValue($this->userId, Application::APP_NAME, $key, $defaultValue);
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function setUserValue(string $key, string $value): string
	{
		return $this->config->setUserValue($this->userId, Application::APP_NAME, $key, $value);
	}

	/**
	 * @param string $userId
	 * @param string $key
	 *
	 * @return string
	 */
	public function getValueForUser(string $userId, string $key): string
	{
		return $this->config->getUserValue($userId, Application::APP_NAME, $key);
	}

	/**
	 * @param string $userId
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function setValueForUser(string $userId, string $key, string $value): string
	{
		return $this->config->setUserValue($userId, Application::APP_NAME, $key, $value);
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	private function getDefaultValue(string $key): string
	{
		return $this->defaults[$key] ?? '';
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getSystemValue(string $key, $default)
	{
		return $this->config->getSystemValue($key, $default);
	}

	/**
	 * return the cloud version.
	 * if $complete is true, return a string x.y.z
	 *
	 * @param boolean $complete
	 *
	 * @return string|integer
	 */
	public function getCloudVersion($complete = false) {
		$ver = Util::getVersion();

		if ($complete) {
			return implode('.', $ver);
		}

		return $ver[0];
	}
}
