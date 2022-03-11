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
			'name' => 'Pico#getAsset',
			'url' => '/pico/{site}/assets/{asset}',
			'verb' => 'GET',
			'requirements' => [ 'asset' => '.+' ]
		],
		[
			'name' => 'Pico#getAsset',
			'postfix' => 'ETag',
			'url' => '/pico/{site}/assets-{assetsETag}/{asset}',
			'verb' => 'GET',
			'requirements' => [ 'asset' => '.+' ]
		],
		[
			'name' => 'Pico#getPage',
			'url' => '/pico/{site}/{page}',
			'verb' => 'GET',
			'defaults' => [ 'page' => '' ],
			'requirements' => [ 'page' => '.*' ]
		],
		[
			'name' => 'Pico#getPage',
			'postfix' => 'Post',
			'url' => '/pico/{site}/{page}',
			'verb' => 'POST',
			'defaults' => [ 'page' => '' ],
			'requirements' => [ 'page' => '.*' ]
		],

		[
			'name' => 'Pico#getAsset',
			'postfix' => 'Proxy',
			'url' => '/pico_proxy/{site}/assets/{asset}',
			'verb' => 'GET',
			'requirements' => [ 'asset' => '.+' ]
		],
		[
			'name' => 'Pico#getAsset',
			'postfix' => 'ProxyETag',
			'url' => '/pico_proxy/{site}/assets-{assetsETag}/{asset}',
			'verb' => 'GET',
			'requirements' => [ 'asset' => '.+' ]
		],
		[
			'name' => 'Pico#getPage',
			'postfix' => 'Proxy',
			'url' => '/pico_proxy/{site}/{page}',
			'verb' => 'GET',
			'defaults' => [ 'page' => '', 'proxyRequest' => true ],
			'requirements' => [ 'page' => '.*' ]
		],
		[
			'name' => 'Pico#getPage',
			'postfix' => 'PostProxy',
			'url' => '/pico_proxy/{site}/{page}',
			'verb' => 'POST',
			'defaults' => [ 'page' => '', 'proxyRequest' => true ],
			'requirements' => [ 'page' => '.*' ]
		],

		[ 'name' => 'Websites#getPersonalWebsites', 'url' => '/personal/websites', 'verb' => 'GET' ],
		[ 'name' => 'Websites#createPersonalWebsite', 'url' => '/personal/websites', 'verb' => 'POST' ],
		[ 'name' => 'Websites#updatePersonalWebsite', 'url' => '/personal/websites/{siteId}', 'verb' => 'POST' ],
		[ 'name' => 'Websites#removePersonalWebsite', 'url' => '/personal/websites/{siteId}', 'verb' => 'DELETE' ],

		[ 'name' => 'Templates#getTemplates', 'url' => '/admin/templates', 'verb' => 'GET' ],
		[ 'name' => 'Templates#addCustomTemplate', 'url' => '/admin/templates', 'verb' => 'POST' ],
		[ 'name' => 'Templates#removeCustomTemplate', 'url' => '/admin/templates/{item}', 'verb' => 'DELETE' ],
		[ 'name' => 'Templates#copyTemplate', 'url' => '/admin/templates/{item}', 'verb' => 'CLONE' ],

		[ 'name' => 'Themes#getThemes', 'url' => '/admin/themes', 'verb' => 'GET' ],
		[ 'name' => 'Themes#addCustomTheme', 'url' => '/admin/themes', 'verb' => 'POST' ],
		[ 'name' => 'Themes#updateCustomTheme', 'url' => '/admin/themes/{item}', 'verb' => 'POST' ],
		[ 'name' => 'Themes#removeCustomTheme', 'url' => '/admin/themes/{item}', 'verb' => 'DELETE' ],
		[ 'name' => 'Themes#copyTheme', 'url' => '/admin/themes/{item}', 'verb' => 'CLONE' ],

		[ 'name' => 'Plugins#getPlugins', 'url' => '/admin/plugins', 'verb' => 'GET' ],
		[ 'name' => 'Plugins#addCustomPlugin', 'url' => '/admin/plugins', 'verb' => 'POST' ],
		[ 'name' => 'Plugins#updateCustomPlugin', 'url' => '/admin/plugins/{item}', 'verb' => 'POST' ],
		[ 'name' => 'Plugins#removeCustomPlugin', 'url' => '/admin/plugins/{item}', 'verb' => 'DELETE' ],
		[ 'name' => 'Plugins#copyDummyPlugin', 'url' => '/admin/plugins/DummyPlugin', 'verb' => 'CLONE' ],

		[ 'name' => 'Settings#setLimitGroups', 'url' => '/admin/limit_groups', 'verb' => 'POST' ],
		[ 'name' => 'Settings#setLinkMode', 'url' => '/admin/link_mode', 'verb' => 'POST' ],
	]
];
