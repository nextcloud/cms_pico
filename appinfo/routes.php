<?php


return [
	'routes' => [
		['name' => 'Navigation#navigate', 'url' => '/', 'verb' => 'GET'],
		[
			'name' => 'Pico#getRoot', 'url' => '/pico/{site}/', 'verb' => 'GET'
		],
		[
			'name' => 'Pico#getPage', 'url' => '/pico/{site}/{page}', 'verb' => 'GET',
			'requirements' => array('page' => '.+')
		],
		['name' => 'Settings#getPersonalWebsites', 'url' => '/personal/websites', 'verb' => 'GET'],
		['name' => 'Settings#createPersonalWebsite', 'url' => '/personal/website', 'verb' => 'PUT'],
		[
			'name' => 'Settings#editPersonalWebsiteOption',
			'url'  => '/personal/website/{siteId}/option/{option}', 'verb' => 'POST'
		],

		['name' => 'Settings#getSettingsAdmin', 'url' => '/admin/settings', 'verb' => 'GET'],
		['name' => 'Settings#setSettingsAdmin', 'url' => '/admin/settings', 'verb' => 'POST'],
	]
];


