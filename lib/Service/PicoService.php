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

namespace OCA\CMSPico\Service;

use Exception;
use HTMLPurifier;
use HTMLPurifier_Config;
use OC\App\AppManager;
use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\PluginNextcloudNotLoadedException;
use OCA\CMSPico\Model\Website;
use Pico;

class PicoService {

	const DIR_CONFIG = 'Pico/config/';
	const DIR_PLUGINS = 'Pico/plugins/';
	const DIR_THEMES = 'Pico/themes/';

	const NC_PLUGIN = 'Nextcloud';

	private $userId;

	/** @var AppManager */
	private $appManager;

	/** @var MiscService */
	private $miscService;

	/**
	 * PicoService constructor.
	 *
	 * @param string $userId
	 * @param AppManager $appManager
	 * @param MiscService $miscService
	 */
	function __construct($userId, AppManager $appManager, MiscService $miscService) {
		$this->userId = $userId;
		$this->appManager = $appManager;
		$this->miscService = $miscService;
	}


	/**
	 * @param Website $website
	 *
	 * @return string
	 * @throws PicoRuntimeException
	 */
	public function getContent(Website $website) {

		$appPath = MiscService::endSlash($this->appManager->getAppPath(Application::APP_NAME));
		$pico = new Pico(
			$website->getAbsolutePath(), $appPath . self::DIR_CONFIG,
			$appPath . self::DIR_PLUGINS, $appPath . self::DIR_THEMES
		);

		$this->generateConfig($pico, $website);
		try {
			$content = $pico->run();
		} catch (\Exception $e) {
			throw new PicoRuntimeException($e->getMessage());
		}

		$this->pluginNextcloudMustBeLoaded($pico);
		$absolutePath = $this->getAbsolutePathFromPage($pico);
		$website->contentMustBeLocal($absolutePath);
		$website->viewerMustHaveAccess($absolutePath, $pico->getFileMeta());

		return $content;
	}


	/**
	 * @param Pico $pico
	 * @param Website $website
	 */
	private function generateConfig(Pico &$pico, Website $website) {
		$pico->setConfig(
			[
				'content_dir' => 'content/',
				'content_ext' => '.md',
				'theme'       => $website->getTheme(),
				'site_title'  => $website->getName(),
				'base_url'    => \OC::$WEBROOT . $website->getSite()
			]
		);
	}


	/**
	 * @param Pico $pico
	 *
	 * @return string
	 */
	private function getAbsolutePathFromPage(Pico $pico) {
		return $pico->getConfig()['content_dir'] . $pico->getCurrentPage()['id'] . '.md';
	}



	private function pluginNextcloudMustBeLoaded(Pico $pico) {
		try {
			$pico->getPlugin(self::NC_PLUGIN);
		} catch (Exception $e)
		{
			throw new PluginNextcloudNotLoadedException($e->getMessage());
		}
	}
}