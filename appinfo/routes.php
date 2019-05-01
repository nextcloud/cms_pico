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

return [
	'routes' => [
		[ 'name' => 'Pico#getRoot', 'url' => '/pico/{site}/', 'verb' => 'GET' ],
		[
			'name' => 'Pico#getPage', 'url' => '/pico/{site}/{page}', 'verb' => 'GET',
			'requirements' => array('page' => '.+')
		],

		[ 'name' => 'Pico#getRootProxy', 'url' => '/pico_proxy/{site}/', 'verb' => 'GET' ],
		[
			'name' => 'Pico#getPageProxy', 'url' => '/pico_proxy/{site}/{page}', 'verb' => 'GET',
			'requirements' => array('page' => '.+'), 'defaults' => array('proxy' => '1')
		],

		[
			'name' => 'Pico#getTheme', 'url' => '/themes/{file}', 'verb' => 'GET',
			'requirements' => array('file' => '.+')
		],
		[
			'name' => 'Pico#getPlugin', 'url' => '/plugins/{file}', 'verb' => 'GET',
			'requirements' => array('file' => '.+')
		],

		['name' => 'Settings#getPersonalWebsites', 'url' => '/personal/websites', 'verb' => 'GET'],
		['name' => 'Settings#createPersonalWebsite', 'url' => '/personal/website', 'verb' => 'PUT'],
		['name' => 'Settings#removePersonalWebsite', 'url' => '/personal/website', 'verb' => 'DELETE'],
		['name' => 'Settings#updateWebsiteTheme', 'url' => '/personal/website/{siteId}/theme', 'verb' => 'PUT'],
		[
			'name' => 'Settings#editPersonalWebsiteOption',
			'url'  => '/personal/website/{siteId}/option/{option}', 'verb' => 'POST'
		],

		['name' => 'Settings#getSettingsAdmin', 'url' => '/admin/settings', 'verb' => 'GET'],
		['name' => 'Settings#setSettingsAdmin', 'url' => '/admin/settings', 'verb' => 'POST'],
		['name' => 'Settings#addCustomTemplate', 'url' => '/admin/templates', 'verb' => 'PUT'],
		['name' => 'Settings#removeCustomTemplate', 'url' => '/admin/templates', 'verb' => 'DELETE'],
		['name' => 'Settings#addCustomTheme', 'url' => '/admin/themes', 'verb' => 'PUT'],
		['name' => 'Settings#removeCustomTheme', 'url' => '/admin/themes', 'verb' => 'DELETE']
	]
];


