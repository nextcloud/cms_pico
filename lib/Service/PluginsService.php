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

namespace OCA\CMSPico\Service;

use OC\App\AppManager;
use OCA\CMSPico\AppInfo\Application;

class PluginsService
{
	/** @var AppManager */
	private $appManager;

	/**
	 * PluginsService constructor.
	 *
	 * @param AppManager $appManager
	 */
	public function __construct(AppManager $appManager)
	{
		$this->appManager = $appManager;
	}

	/**
	 * @return string
	 */
	public function getPluginsPath(): string
	{
		$appPath = $this->appManager->getAppPath(Application::APP_NAME);
		return $appPath . '/appdata_public/' . PicoService::DIR_PLUGINS . '/';
	}

	/**
	 * @return string
	 */
	public function getPluginsUrl(): string
	{
		return \OC_App::getAppWebPath(Application::APP_NAME) . '/appdata_public/' . PicoService::DIR_PLUGINS . '/';
	}
}
