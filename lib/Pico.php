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

declare(strict_types=1);

namespace OCA\CMSPico;

use HTMLPurifier;
use HTMLPurifier_Config;
use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Exceptions\PageNotFoundException;
use OCA\CMSPico\Exceptions\PageNotPermittedException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Model\Website;
use Symfony\Component\Yaml\Exception\ParseException;

class Pico extends \Pico
{
	/** @var HTMLPurifier */
	protected $htmlPurifier;

	/** @var Website */
	private $website;

	/**
	 * {@inheritDoc}
	 *
	 * @throws PageInvalidPathException
	 * @throws PageNotFoundException
	 * @throws PageNotPermittedException
	 */
	public function run()
	{
		return parent::run();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function loadConfig()
	{
		$themeUrl = $this->config['theme_url'] ?? null;

		parent::loadConfig();

		if ($themeUrl && ($themeUrl[0] === '/')) {
			$this->config['theme_url'] = $themeUrl;
		}

		if (empty($this->config['nextcloud_site'])) {
			$this->config['nextcloud_site'] = 'default';
		}
	}

	/**
	 * Set's Nextcloud's website instance.
	 *
	 * @param Website $website Nextcloud's website instance
	 *
	 * @return void
	 */
	public function setNextcloudWebsite(Website $website)
	{
		$this->website = $website;
	}

	/**
	 * Set's Pico's request URL.
	 *
	 * @param string $requestUrl request URL
	 *
	 * @return void
	 */
	public function setRequestUrl($requestUrl)
	{
		$this->requestUrl = $requestUrl;
	}

	/**
	 * Don't let Pico evaluate the request URL.
	 *
	 * @see Pico::setRequestUrl()
	 *
	 * @return void
	 */
	protected function evaluateRequestUrl()
	{
		// do nothing
	}

	/**
	 * Checks whether a file is readable in Nextcloud and returns the raw contents of this file
	 *
	 * @param string $file file path
	 *
	 * @return string raw contents of the file
	 *
	 * @throws WebsiteNotFoundException
	 * @throws PageInvalidPathException
	 * @throws PageNotFoundException
	 * @throws PageNotPermittedException
	 */
	public function loadFileContent($file)
	{
		return $this->website->getFileContent($file);
	}

	/**
	 * Returns the parsed and purified file meta from raw file contents.
	 *
	 * @param  string $rawContent
	 * @param  string[] $headers
	 *
	 * @return array
	 * @throws ParseException
	 */
	public function parseFileMeta($rawContent, array $headers)
	{
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
	protected function purifyFileMeta(array $meta)
	{
		$newMeta = [];
		foreach ($meta as $key => $value) {
			if (is_array($value)) {
				$newMeta[$key] = $this->purifyFileMeta($value);
			} else {
				$newMeta[$key] = $this->getHtmlPurifier()->purify($value);
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
	public function parseFileContent($markdown)
	{
		$content = parent::parseFileContent($markdown);
		return $this->getHtmlPurifier()->purify($content);
	}

	/**
	 * Returns the HTMLPurifier instance.
	 *
	 * @return HTMLPurifier
	 */
	public function getHtmlPurifier()
	{
		if ($this->htmlPurifier === null) {
			$this->htmlPurifier = new HTMLPurifier(HTMLPurifier_Config::createDefault());
		}

		return $this->htmlPurifier;
	}
}
