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
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Service\TemplatesService;
use OCA\CMSPico\Service\ThemesService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class Personal implements ISettings
{
	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var WebsitesService */
	private $websitesService;

	/** @var ThemesService */
	private $themesService;

	/** @var TemplatesService */
	private $templatesService;

	/**
	 * Personal constructor.
	 *
	 * @param IURLGenerator    $urlGenerator
	 * @param WebsitesService  $websitesService
	 * @param ThemesService    $themesService
	 * @param TemplatesService $templatesService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		WebsitesService $websitesService,
		ThemesService $themesService,
		TemplatesService $templatesService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->websitesService = $websitesService;
		$this->themesService = $themesService;
		$this->templatesService = $templatesService;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse
	{
		$exampleSite = 'example_site';

		$baseUrl = $this->urlGenerator->getBaseUrl() . '/index.php/apps/' . Application::APP_NAME . '/pico/';
		if ($this->websitesService->getLinkMode() === WebsitesService::LINK_MODE_SHORT) {
			$baseUrl = $this->urlGenerator->getBaseUrl() . '/sites/';
		}

		$data = [
			'exampleSite' => $exampleSite,
			'baseUrl' => $baseUrl,
			'nameLengthMin' => Website::NAME_LENGTH_MIN,
			'nameLengthMax' => Website::NAME_LENGTH_MAX,
			'siteLengthMin' => Website::SITE_LENGTH_MIN,
			'siteLengthMax' => Website::SITE_LENGTH_MAX,
			'siteRegex' => Website::SITE_REGEX,
			'themes' => $this->themesService->getThemes(),
			'templates' => $this->templatesService->getTemplates()
		];

		return new TemplateResponse(Application::APP_NAME, 'settings.personal', $data);
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
