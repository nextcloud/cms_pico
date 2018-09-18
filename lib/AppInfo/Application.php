<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\CMSPico\AppInfo;

use OCP\AppFramework\App;
use OCP\Util;

class Application extends App {

	const APP_NAME = 'cms_pico';

	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct(self::APP_NAME, $params);

		$this->loadIcons();
		$this->registerHooks();
	}


	protected function loadIcons() {
		Util::addStyle(self::APP_NAME, 'icons');
	}

	/**
	 * Register Hooks
	 */
	public function registerHooks() {
		Util::connectHook(
			'OC_User', 'post_deleteUser', '\OCA\CMSPico\Hooks\UserHooks', 'onUserDeleted'
		);
	}

	public function registerSettingsPersonal() {
		$ver = Util::getVersion();
		if ($ver[0] < 13) {
			\OCP\App::registerPersonal(self::APP_NAME, 'lib/personal');
		}
	}
}

