<?php

declare(strict_types=1);

namespace OCA\CMSPico\Service;

use OC\App\AppManager;
use OCA\CMSPico\AppInfo\Application;

/**
 * @TODO Pico 2.0.5-6 sollte explizit ein plugins_url bekommen das wir dann hier verwenden können
 */
class PluginsService
{
	/** @var AppManager */
	private $appManager;

	/**
	 * PluginsService constructor.
	 *
	 * @param AppManager $appManager
	 */
	function __construct(AppManager $appManager)
	{
		$this->appManager = $appManager;
	}

	/**
	 * @return string
	 */
	public function getPluginsPath() : string
	{
		$appPath = $this->appManager->getAppPath(Application::APP_NAME);
		return $appPath . '/Pico/' . PicoService::DIR_PLUGINS . '/';
	}

	/**
	 * @return string
	 */
	public function getPluginsUrl() : string
	{
		return \OC_App::getAppWebPath(Application::APP_NAME) . '/Pico/' . PicoService::DIR_PLUGINS . '/';
	}
}
