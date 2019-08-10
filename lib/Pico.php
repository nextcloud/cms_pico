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

namespace OCA\CMSPico;

use HTMLPurifier;
use HTMLPurifier_Config;
use OCA\CMSPico\Exceptions\WebsiteInvalidFilesystemException;
use OCA\CMSPico\Files\FileInterface;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Service\PicoService;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Symfony\Component\Yaml\Exception\ParseException;

class Pico extends \Pico
{
	/** @var PicoService */
	private $picoService;

	/** @var HTMLPurifier */
	private $htmlPurifier;

	/** @var Website */
	private $website;

	/**
	 * Pico constructor.
	 *
	 * {@inheritDoc}
	 */
	public function __construct($rootDir, $configDir, $pluginsDir, $themesDir, $enableLocalPlugins = true)
	{
		$this->picoService = \OC::$server->query(PicoService::class);

		parent::__construct($rootDir, $configDir, $pluginsDir, $themesDir, $enableLocalPlugins);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 * @throws WebsiteInvalidFilesystemException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function run()
	{
		return parent::run();
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
	 * @param string $absolutePath file path
	 *
	 * @return string raw contents of the file
	 * @throws WebsiteInvalidFilesystemException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function loadFileContent($absolutePath)
	{
		/** @var FolderInterface $folder */
		/** @var string $basePath */
		/** @var string $relativePath */
		list($folder, $basePath, $relativePath) = $this->picoService->getRelativePath($this->website, $absolutePath);

		/** @var FileInterface $file */
		$file = $folder->get($relativePath);
		if (!$file->isFile()) {
			throw new InvalidPathException();
		}

		return $file->getContent();
	}

	/**
	 * Returns the parsed and purified file meta from raw file contents.
	 *
	 * @param string $rawContent
	 * @param string[] $headers
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
	 * @param string $markdown
	 *
	 * @return string
	 */
	public function parseFileContent($markdown)
	{
		$content = parent::parseFileContent($markdown);
		return $this->purifyFileContent($content);
	}

	/**
	 * Purifies file content.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	protected function purifyFileContent(string $content)
	{
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

			$this->triggerEvent('onHtmlPurifier', [ &$this->htmlPurifier ]);
		}

		return $this->htmlPurifier;
	}
}
