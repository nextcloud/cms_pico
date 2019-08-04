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

return [
	'routes' => [
		[
			'name' => 'Pico#getRoot',
			'url' => '/pico/{site}/',
			'verb' => 'GET'
		],
		[
			'name' => 'Pico#getAsset',
			'url' => '/pico/{site}/assets/{asset}',
			'verb' => 'GET',
			'requirements' => [ 'asset' => '.+' ]
		],
		[
			'name' => 'Pico#getPage',
			'url' => '/pico/{site}/{page}',
			'verb' => 'GET',
			'requirements' => [ 'page' => '.+' ]
		],

		[
			'name' => 'Pico#getRoot',
			'postfix' => 'Proxy',
			'url' => '/pico_proxy/{site}/',
			'verb' => 'GET',
			'defaults' => [ 'proxyRequest' => true ]
		],
		[
			'name' => 'Pico#getAsset',
			'postfix' => 'Proxy',
			'url' => '/pico_proxy/{site}/assets/{asset}',
			'verb' => 'GET',
			'requirements' => [ 'asset' => '.+' ]
		],
		[
			'name' => 'Pico#getPage',
			'postfix' => 'Proxy',
			'url' => '/pico_proxy/{site}/{page}',
			'verb' => 'GET',
			'defaults' => [ 'proxyRequest' => true ],
			'requirements' => [ 'page' => '.+' ]
		],

		[ 'name' => 'Settings#getPersonalWebsites', 'url' => '/personal/websites', 'verb' => 'GET' ],
		[ 'name' => 'Settings#createPersonalWebsite', 'url' => '/personal/websites', 'verb' => 'POST' ],
		[ 'name' => 'Settings#updatePersonalWebsite', 'url' => '/personal/websites/{siteId}', 'verb' => 'POST' ],
		[ 'name' => 'Settings#removePersonalWebsite', 'url' => '/personal/websites/{siteId}', 'verb' => 'DELETE' ],

		[ 'name' => 'Settings#getTemplates', 'url' => '/admin/templates', 'verb' => 'GET' ],
		[ 'name' => 'Settings#addCustomTemplate', 'url' => '/admin/templates', 'verb' => 'POST' ],
		[ 'name' => 'Settings#removeCustomTemplate', 'url' => '/admin/templates/{item}', 'verb' => 'DELETE' ],

		[ 'name' => 'Settings#getThemes', 'url' => '/admin/themes', 'verb' => 'GET' ],
		[ 'name' => 'Settings#addCustomTheme', 'url' => '/admin/themes', 'verb' => 'POST' ],
		[ 'name' => 'Settings#updateCustomTheme', 'url' => '/admin/themes/{item}', 'verb' => 'POST' ],
		[ 'name' => 'Settings#removeCustomTheme', 'url' => '/admin/themes/{item}', 'verb' => 'DELETE' ],

		[ 'name' => 'Settings#getPlugins', 'url' => '/admin/plugins', 'verb' => 'GET' ],
		[ 'name' => 'Settings#addCustomPlugin', 'url' => '/admin/plugins', 'verb' => 'POST' ],
		[ 'name' => 'Settings#updateCustomPlugin', 'url' => '/admin/plugins/{item}', 'verb' => 'POST' ],
		[ 'name' => 'Settings#removeCustomPlugin', 'url' => '/admin/plugins/{item}', 'verb' => 'DELETE' ],

		[ 'name' => 'Settings#setLinkMode', 'url' => '/admin/link_mode', 'verb' => 'POST' ],
	]
];


