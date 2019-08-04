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

namespace OCA\CMSPico\Settings;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Service\FileService;
use OCA\CMSPico\Service\PicoService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class Admin implements ISettings
{
	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var FileService */
	private $fileService;

	/**
	 * Admin constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param FileService   $fileService
	 */
	public function __construct(IURLGenerator $urlGenerator, FileService $fileService)
	{
		$this->urlGenerator = $urlGenerator;
		$this->fileService = $fileService;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse
	{
		$exampleSite = 'example_site';
		$exampleProxyUrl = $this->urlGenerator->getBaseUrl() . '/sites/' . urlencode($exampleSite);
		$exampleFullUrl = $this->urlGenerator->linkToRouteAbsolute(
			Application::APP_NAME . '.Pico.getPage',
			[ 'site' => $exampleSite, 'page' => '' ]
		);

		$internalBaseUrl = $this->urlGenerator->getBaseUrl() . '/index.php/apps/' . Application::APP_NAME;
		$internalBasePath = \OC::$WEBROOT;

		$data = [
			'exampleProxyUrl'  => $exampleProxyUrl . '/',
			'exampleFullUrl'   => $exampleFullUrl . '/',
			'internalProxyUrl' => $internalBaseUrl . '/pico_proxy/',
			'internalFullUrl'  => $internalBaseUrl . '/pico/',
			'internalPath'     => $internalBasePath . '/sites/',
			'themesPath'       => $this->fileService->getAppDataFolderPath(PicoService::DIR_THEMES, true),
			'pluginsPath'      => $this->fileService->getAppDataFolderPath(PicoService::DIR_PLUGINS, true),
			'templatesPath'    => $this->fileService->getAppDataFolderPath(PicoService::DIR_TEMPLATES, true)
		];

		return new TemplateResponse(Application::APP_NAME, 'settings.admin', $data);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string
	{
		return Application::APP_NAME;
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * keep the server setting at the top, right after "server settings"
	 */
	public function getPriority(): int
	{
		return 0;
	}
}
