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
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\PicoRuntimeException;
use OCA\CMSPico\Exceptions\ThemeNotCompatibleException;
use OCA\CMSPico\Exceptions\ThemeNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Model\PicoPage;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Pico;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\ILogger;

class PicoService
{
	/** @var string */
	const DIR_TEMPLATES = 'templates';

	/** @var string */
	const DIR_CONFIG = 'config';

	/** @var string */
	const DIR_PLUGINS = 'plugins';

	/** @var string */
	const DIR_THEMES = 'themes';

	/** @var string */
	const DIR_CONTENT = 'content';

	/** @var string */
	const DIR_ASSETS = 'assets';

	/** @var ILogger */
	private $logger;

	/** @var AssetsService */
	private $assetsService;

	/** @var ThemesService */
	private $themesService;

	/** @var PluginsService */
	private $pluginsService;

	/** @var FileService */
	private $fileService;

	/** @var MiscService */
	private $miscService;

	/**
	 * PicoService constructor.
	 *
	 * @param ILogger        $logger
	 * @param AssetsService  $assetsService
	 * @param ThemesService  $themesService
	 * @param PluginsService $pluginsService
	 * @param FileService    $fileService
	 * @param MiscService    $miscService
	 */
	public function __construct(
		ILogger $logger,
		AssetsService $assetsService,
		ThemesService $themesService,
		PluginsService $pluginsService,
		FileService $fileService,
		MiscService $miscService
	) {
		$this->logger = $logger;
		$this->assetsService = $assetsService;
		$this->themesService = $themesService;
		$this->pluginsService = $pluginsService;
		$this->fileService = $fileService;
		$this->miscService = $miscService;
	}

	/**
	 * @param Website $website
	 *
	 * @return PicoPage
	 * @throws WebsiteNotFoundException
	 * @throws WebsiteNotPermittedException
	 * @throws ThemeNotFoundException
	 * @throws ThemeNotCompatibleException
	 * @throws PageInvalidPathException
	 * @throws PageNotFoundException
	 * @throws PageNotPermittedException
	 * @throws PicoRuntimeException
	 */
	public function getPage(Website $website): PicoPage
	{
		try {
			$page = $website->getPage();
			$page = $this->miscService->normalizePath($page);

			$website->assertViewerAccess($page);

			$this->themesService->assertValidTheme($website->getTheme());

			$pico = new Pico(
				$website->getWebsitePath(),
				$this->fileService->getAppDataFolderPath(self::DIR_CONFIG, true),
				$this->pluginsService->getPluginsPath(),
				$this->themesService->getThemesPath(),
				false
			);

			try {
				$this->setupPico($website, $pico, $page);
				$this->loadPicoPlugins($pico);

				$output = $pico->run();
			} catch (PageInvalidPathException $e) {
				throw $e;
			} catch (PageNotFoundException $e) {
				throw $e;
			} catch (PageNotPermittedException $e) {
				throw $e;
			} catch (\Exception $e) {
				$exception = new PicoRuntimeException($e);
				$this->logger->logException($exception, [ 'app' => Application::APP_NAME ]);
				throw $exception;
			}

			$picoPage = new PicoPage($website, $pico, $output);
			$website->assertViewerAccess($picoPage->getRelativePath(), $picoPage->getMeta());
		} catch (InvalidPathException $e) {
			throw new PageInvalidPathException($e);
		} catch (NotFoundException $e) {
			throw new PageNotFoundException($e);
		} catch (NotPermittedException $e) {
			throw new PageNotPermittedException($e);
		}

		return $picoPage;
	}

	/**
	 * @param Website $website
	 * @param Pico $pico
	 * @param string $page
	 */
	private function setupPico(Website $website, Pico $pico, string $page)
	{
		$pico->setRequestUrl($page);
		$pico->setNextcloudWebsite($website);

		$pico->setConfig(
			[
				'site_title'     => $website->getName(),
				'base_url'       => $website->getWebsiteUrl(),
				'rewrite_url'    => true,
				'debug'          => \OC::$server->getConfig()->getSystemValue('debug', false),
				'timezone'       => $website->getTimeZone(),
				'theme'          => $website->getTheme(),
				'themes_url'     => $this->themesService->getThemesUrl(),
				'content_dir'    => self::DIR_CONTENT,
				'content_ext'    => '.md',
				'assets_dir'     => self::DIR_ASSETS,
				'assets_url'     => $this->assetsService->getAssetsUrl($website),
				'plugins_url'    => $this->pluginsService->getPluginsUrl(),
				'nextcloud_site' => $website->getSite(),
			]
		);
	}

	/**
	 * @param Pico $pico
	 */
	private function loadPicoPlugins(Pico $pico)
	{
		$includeClosure = static function (string $pluginFile) {
			require($pluginFile);
		};

		$plugins = $this->pluginsService->getPlugins();
		foreach ($plugins as $pluginData) {
			if ($pluginData['compat']) {
				$pluginFile = $pluginData['name'] . '/' . $pluginData['name'] . '.php';
				$includeClosure($this->pluginsService->getPluginsPath() . '/' . $pluginFile);

				$pico->loadPlugin($pluginData['name']);
			}
		}
	}
}
