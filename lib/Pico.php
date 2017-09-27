<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Daniel rudolf <www.daniel-rudolf.de>
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
 */

namespace OCA\CMSPico;

use HTMLPurifier;
use HTMLPurifier_Config;
use OCA\CMSPico\AppInfo\Application;

class Pico extends \Pico {

	/** @var HTMLPurifier */
	protected $htmlPurifier;

	/**
	 * Loads the config.php from Pico::$configDir.
	 *
	 * We force enabled URL rewriting due to the support of Nextcloud's
	 * `PATH_INFO`-based routing method ({@see self::evaluateRequestUrl()}).
	 */
	protected function loadConfig() {
		parent::loadConfig();

		$this->config['rewrite_url'] = true;

		if (empty($this->config['nextcloud_site'])) {
			$this->config['nextcloud_site'] = 'default';
		}
	}


	/**
	 * Evaluates the requested URL.
	 *
	 * Besides Pico's built-in `QUERY_STRING`-based routing (e.g. `?sub/page`),
	 * we additionally fully support Nextcloud's `PATH_INFO`-based routing
	 * (e.g. `/index.php/apps/cms_pico/pico/sub/page`).
	 */
	protected function evaluateRequestUrl() {
		parent::evaluateRequestUrl();

		if (!$this->requestUrl) {
			$pathInfo = \OC::$server->getRequest()
									->getRawPathInfo();
			if ($pathInfo) {
				$basePathInfo =
					\OC::$WEBROOT . '/apps/' . Application::APP_NAME . '/pico/' . $this->getConfig(
						'nextcloud_site'
					) . '/';
				$basePathInfoLength = strlen($basePathInfo);
				if (substr($pathInfo, 0, $basePathInfoLength) === $basePathInfo) {
					$this->requestUrl = substr($pathInfo, $basePathInfoLength);
					$this->requestUrl = trim($this->requestUrl, '/');
				}
			}
		}
	}


	/**
	 * Returns the parsed and purified file meta from raw file contents.
	 *
	 * @param  string $rawContent
	 * @param  string[] $headers
	 *
	 * @return array
	 * @throws \Symfony\Component\Yaml\Exception\ParseException
	 */
	public function parseFileMeta($rawContent, array $headers) {
		$meta = parent::parseFileMeta($rawContent, $headers);

		return $this->purifyFileMeta($meta);
	}


	/**
	 * Purifies file meta.
	 *
	 * @param array $meta
	 *
	 * @return array
	 */
	protected function purifyFileMeta(array $meta) {
		$newMeta = [];
		foreach ($meta as $key => $value) {
			if (is_array($value)) {
				$newMeta[$key] = $this->purifyFileMeta($value);
			} else {
				$newMeta[$key] = $this->getHtmlPurifier()
									  ->purify($value);
			}
		}

		return $newMeta;
	}


	/**
	 * Returns the parsed and purified contents of a page.
	 *
	 * @param  string $markdown
	 *
	 * @return string
	 */
	public function parseFileContent($markdown) {
		$content = parent::parseFileContent($markdown);

		return $this->getHtmlPurifier()
					->purify($content);
	}


	/**
	 * Returns the variables passed to the template.
	 *
	 * Let Pico's `theme_url` point directly to the app's themes directory.
	 *
	 * @return array
	 */
	protected function getTwigVariables() {
		$twigVariables = parent::getTwigVariables();
		$twigVariables['theme_url'] =
			\OC_App::getAppWebPath(Application::APP_NAME) . '/Pico/themes/' . $this->getConfig('theme');

		return $twigVariables;
	}


	/**
	 * Returns the HTMLPurifier instance.
	 *
	 * @return HTMLPurifier
	 */
	public function getHtmlPurifier() {
		if ($this->htmlPurifier === null) {
			$this->htmlPurifier = new HTMLPurifier(HTMLPurifier_Config::createDefault());
		}

		return $this->htmlPurifier;
	}
}
