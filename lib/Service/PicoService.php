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
use OCA\CMSPico\Exceptions\WebsiteInvalidFilesystemException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Files\StorageFolder;
use OCA\CMSPico\Model\PicoPage;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Model\WebsiteRequest;
use OCA\CMSPico\Pico;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Psr\Log\LoggerInterface;

class PicoService
{
	/** @var string */
	public const DIR_TEMPLATES = 'templates';

	/** @var string */
	public const DIR_CONFIG = 'config';

	/** @var string */
	public const DIR_PLUGINS = 'plugins';

	/** @var string */
	public const DIR_THEMES = 'themes';

	/** @var string */
	public const DIR_CONTENT = 'content';

	/** @var string */
	public const DIR_ASSETS = 'assets';

	/** @var string */
	public const CONTENT_EXT = '.md';

	/** @var LoggerInterface */
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
	 * @param LoggerInterface $logger
	 * @param AssetsService   $assetsService
	 * @param ThemesService   $themesService
	 * @param PluginsService  $pluginsService
	 * @param FileService     $fileService
	 * @param MiscService     $miscService
	 */
	public function __construct(
		LoggerInterface $logger,
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
	 * @param WebsiteRequest $websiteRequest
	 *
	 * @return PicoPage
	 * @throws WebsiteInvalidFilesystemException
	 * @throws WebsiteNotPermittedException
	 * @throws ThemeNotFoundException
	 * @throws ThemeNotCompatibleException
	 * @throws PageInvalidPathException
	 * @throws PageNotFoundException
	 * @throws PageNotPermittedException
	 * @throws PicoRuntimeException
	 */
	public function getPage(WebsiteRequest $websiteRequest): PicoPage
	{
		$website = $websiteRequest->getWebsite();
		$page = $websiteRequest->getPage();

		try {
			$websiteRequest->assertViewerAccess(self::DIR_CONTENT . '/' . ($page ?: 'index') . self::CONTENT_EXT);

			$this->themesService->assertValidTheme($website->getTheme());

			$pico = new Pico(
				$website->getWebsitePath(),
				$this->getConfigPath(),
				$this->pluginsService->getPluginsPath(),
				$this->themesService->getThemesPath(),
				false
			);

			try {
				$this->setupPico($websiteRequest, $pico);
				$this->loadPicoPlugins($pico);

				$output = $pico->run();
			} catch (WebsiteInvalidFilesystemException $e) {
				throw $e;
			} catch (InvalidPathException | NotFoundException | NotPermittedException $e) {
				throw $e;
			} catch (\Exception $e) {
				$exception = new PicoRuntimeException($e);
				$this->logger->error($exception, [ 'app' => Application::APP_NAME ]);
				throw $exception;
			}

			$picoPage = new PicoPage($websiteRequest, $pico, $output);

			$picoPagePath = self::DIR_CONTENT . '/' . $picoPage->getRelativePath() . self::CONTENT_EXT;
			$websiteRequest->assertViewerAccess($picoPagePath, $picoPage->getMeta());
		} catch (InvalidPathException $e) {
			throw new PageInvalidPathException($website->getSite(), $page, $e);
		} catch (NotFoundException $e) {
			throw new PageNotFoundException($website->getSite(), $page, $e);
		} catch (NotPermittedException $e) {
			throw new PageNotPermittedException($website->getSite(), $page, $e);
		}

		return $picoPage;
	}

	/**
	 * @param WebsiteRequest $websiteRequest
	 * @param Pico           $pico
	 *
	 * @throws WebsiteInvalidFilesystemException
	 */
	private function setupPico(WebsiteRequest $websiteRequest, Pico $pico): void
	{
		$website = $websiteRequest->getWebsite();

		$pico->setRequestUrl($websiteRequest->getPage());
		$pico->setNextcloudWebsite($websiteRequest);

		$pico->setConfig(
			[
				'site_title'     => $website->getName(),
				'base_url'       => $this->getWebsiteUrl($websiteRequest),
				'rewrite_url'    => true,
				'debug'          => \OC::$server->getConfig()->getSystemValue('debug', false),
				'timezone'       => $website->getTimeZone(),
				'theme'          => $website->getTheme(),
				'themes_url'     => $this->themesService->getThemesUrl(),
				'content_dir'    => $this->getContentPath($website),
				'content_ext'    => self::CONTENT_EXT,
				'assets_dir'     => $this->assetsService->getAssetsPath($website),
				'assets_url'     => $this->assetsService->getAssetsUrl($websiteRequest),
				'plugins_url'    => $this->pluginsService->getPluginsUrl(),
				'nextcloud_site' => $website->getSite(),
			]
		);
	}

	/**
	 * @param Pico $pico
	 */
	private function loadPicoPlugins(Pico $pico): void
	{
		$includeClosure = static function (string $pluginFile) {
			/** @noinspection PhpIncludeInspection */
			require_once($pluginFile);
		};

		foreach ($this->pluginsService->getPlugins() as $pluginData) {
			if ($pluginData['compat']) {
				$pluginFile = $pluginData['name'] . '/' . $pluginData['name'] . '.php';
				$includeClosure($this->pluginsService->getPluginsPath() . $pluginFile);

				$pico->loadPlugin($pluginData['name']);
			}
		}
	}

	/**
	 * @param Website $website
	 * @param string  $absolutePath
	 *
	 * @return array
	 * @throws WebsiteInvalidFilesystemException
	 * @throws InvalidPathException
	 */
	public function getRelativePath(Website $website, string $absolutePath): array
	{
		$folder = $website->getWebsiteFolder();
		$basePath = $website->getWebsitePath();

		try {
			$relativePath = $this->miscService->getRelativePath($absolutePath, $basePath);
		} catch (InvalidPathException $e) {
			$folder = $this->pluginsService->getPluginsFolder();
			$basePath = $this->pluginsService->getPluginsPath();

			try {
				$relativePath = $this->miscService->getRelativePath($absolutePath, $basePath);
			} catch (InvalidPathException $e) {
				$folder = $this->themesService->getThemesFolder();
				$basePath = $this->themesService->getThemesPath();

				try {
					$relativePath = $this->miscService->getRelativePath($absolutePath, $basePath);
				} catch (InvalidPathException $e) {
					$folder = $this->getConfigFolder();
					$basePath = $this->getConfigPath();

					try {
						$relativePath = $this->miscService->getRelativePath($absolutePath, $basePath);
					} catch (InvalidPathException $e) {
						// the file is neither in the content nor assets, plugins, themes or config folder
						// Pico mustn't have access to any other directory
						throw new InvalidPathException();
					}
				}
			}
		}

		return [ $folder, rtrim($basePath, '/'), $relativePath ];
	}

	/**
	 * @param Website $website
	 *
	 * @return StorageFolder
	 * @throws WebsiteInvalidFilesystemException
	 */
	public function getContentFolder(Website $website): StorageFolder
	{
		try {
			/** @var StorageFolder $websiteFolder */
			$websiteFolder = $website->getWebsiteFolder()->getFolder(PicoService::DIR_CONTENT)->fakeRoot();
			return $websiteFolder;
		} catch (InvalidPathException | NotFoundException $e) {
			throw new WebsiteInvalidFilesystemException($website->getSite(), $e);
		}
	}

	/**
	 * @param Website $website
	 *
	 * @return string
	 * @throws WebsiteInvalidFilesystemException
	 */
	public function getContentPath(Website $website): string
	{
		try {
			return $this->getContentFolder($website)->getLocalPath() . '/';
		} catch (InvalidPathException | NotFoundException $e) {
			throw new WebsiteInvalidFilesystemException($website->getSite(), $e);
		}
	}

	/**
	 * @return StorageFolder
	 */
	public function getConfigFolder(): StorageFolder
	{
		/** @var StorageFolder $configFolder */
		$configFolder = $this->fileService->getAppDataFolder(self::DIR_CONFIG)->fakeRoot();
		return $configFolder;
	}

	/**
	 * @return string
	 */
	public function getConfigPath(): string
	{
		return $this->fileService->getAppDataFolderPath(self::DIR_CONFIG);
	}

	/**
	 * @param WebsiteRequest $websiteRequest
	 *
	 * @return string
	 */
	public function getWebsiteUrl(WebsiteRequest $websiteRequest): string
	{
		$website = $websiteRequest->getWebsite();
		return $website->getWebsiteUrl($websiteRequest->isProxyRequest());
	}
}
