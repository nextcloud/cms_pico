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
use HTMLPurifier_HTML5Config;
use OCA\CMSPico\Exceptions\WebsiteInvalidFilesystemException;
use OCA\CMSPico\Files\FileInterface;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\Glob\GlobIterator;
use OCA\CMSPico\Files\NodeInterface;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Model\WebsiteRequest;
use OCA\CMSPico\Service\PicoService;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Symfony\Component\Yaml\Exception\ParseException;

class Pico extends \Pico
{
	/**
	 * API version 0, used by Pico 0.9 and earlier
	 *
	 * @var int
	 */
	public const API_VERSION_0 = 0;

	/**
	 * API version 1, used by Pico 1.0
	 *
	 * @var int
	 */
	public const API_VERSION_1 = 1;

	/**
	 * API version 2, used by Pico 2.0
	 *
	 * @var int
	 */
	public const API_VERSION_2 = 2;

	/**
	 * API version 3, used by Pico 2.1
	 *
	 * @var int
	 */
	public const API_VERSION_3 = 3;

	/**
	 * API version 4, used by Pico 3.0
	 *
	 * @var int
	 */
	public const API_VERSION_4 = 4;

	/** @var PicoService */
	private $picoService;

	/** @var HTMLPurifier */
	private $htmlPurifier;

	/** @var WebsiteRequest */
	private $websiteRequest;

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
	public function run() : string
	{
		return parent::run();
	}

	/**
	 * Set's Nextcloud's website and website request instances.
	 *
	 * @param WebsiteRequest $websiteRequest Nextcloud's website request instance
	 *
	 * @return void
	 */
	public function setNextcloudWebsite(WebsiteRequest $websiteRequest)
	{
		$this->websiteRequest = $websiteRequest;
		$this->website = $websiteRequest->getWebsite();
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
	protected function evaluateRequestUrl() : void
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
	public function loadFileContent($absolutePath) : string
	{
		/** @var FolderInterface $folder */
		/** @var string $basePath */
		/** @var string $relativePath */
		[ $folder, $basePath, $relativePath ] = $this->picoService->getRelativePath($this->website, $absolutePath);

		$file = $folder->getFile($relativePath);
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
	public function parseFileMeta($rawContent, array $headers) : array
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
	protected function purifyFileMeta(array $meta): array
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
	 * @param bool   $singleLine
	 *
	 * @return string
	 */
	public function parseFileContent($markdown, $singleLine = false) : string
	{
		$content = parent::parseFileContent($markdown, $singleLine);
		return $this->purifyFileContent($content);
	}

	/**
	 * Purifies file content.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	protected function purifyFileContent(string $content): string
	{
		return $this->getHtmlPurifier()->purify($content);
	}

	/**
	 * Returns the HTMLPurifier instance.
	 *
	 * @return HTMLPurifier
	 */
	public function getHtmlPurifier(): HTMLPurifier
	{
		if ($this->htmlPurifier === null) {
			$this->htmlPurifier = new HTMLPurifier($this->getHtmlPurifierConfig());

			$this->triggerEvent('onHtmlPurifier', [ &$this->htmlPurifier ]);
		}

		return $this->htmlPurifier;
	}

	/**
	 * Returns the HTMLPurifier_Config instance.
	 *
	 * @return HTMLPurifier_Config
	 */
	private function getHtmlPurifierConfig(): HTMLPurifier_Config
	{
		$config = HTMLPurifier_HTML5Config::createDefault();
		$config->autoFinalize = false;

		$config->set('Attr.EnableID', true);

		$allowedSchemes = array_merge($config->get('URI.AllowedSchemes'), [ 'data' => true ]);
		$config->set('URI.AllowedSchemes', $allowedSchemes);

		$config->set('HTML.Allowed', 'a[href|target]');
		$config->set('Attr.AllowedFrameTargets', [ '_blank' ]);

		$config->finalize();

		return $config;
	}

	/**
	 * @param string $absolutePath
	 * @param string $fileExtension
	 * @param int    $order
	 *
	 * @return string[]
	 * @throws WebsiteInvalidFilesystemException
	 * @throws InvalidPathException
	 */
	public function getFiles($absolutePath, $fileExtension = '', $order = \Pico::SORT_ASC) : array
	{
		/** @var FolderInterface $folder */
		/** @var string $basePath */
		/** @var string $relativePath */
		[ $folder, $basePath, $relativePath ] = $this->picoService->getRelativePath($this->website, $absolutePath);

		if ($folder->isLocal()) {
			return parent::getFiles($absolutePath, $fileExtension, $order);
		}

		$folderFilter = function (NodeInterface $node, int $key, FolderInterface $folder) use ($fileExtension) {
			$fileName = $node->getName();

			// exclude hidden files/dirs starting with a .
			// exclude files ending with a ~ (vim/nano backup) or # (emacs backup)
			if (($fileName[0] === '.') || in_array($fileName[-1], [ '~', '#' ], true)) {
				return false;
			}

			if ($node->isFile()) {
				/** @var FileInterface $node */
				if ($fileExtension && ($fileExtension !== '.' . $node->getExtension())) {
					return false;
				}
			}

			return true;
		};

		try {
			$folderIterator = new \RecursiveCallbackFilterIterator($folder->fakeRoot(), $folderFilter);

			$result = [];
			foreach (new \RecursiveIteratorIterator($folderIterator) as $file) {
				$result[] = $basePath . '/' . $relativePath . $file->getPath();
			}

			return ($order === \Pico::SORT_DESC) ? array_reverse($result) : $result;
		} catch (\Exception $e) {
			return [];
		}
	}

	/**
	 * @param string $absolutePathPattern
	 * @param int    $order
	 *
	 * @return string[]
	 * @throws WebsiteInvalidFilesystemException
	 * @throws InvalidPathException
	 */
	public function getFilesGlob($absolutePathPattern, $order = \Pico::SORT_ASC) : array
	{
		/** @var FolderInterface $folder */
		/** @var string $basePath */
		/** @var string $pattern */
		[ $folder, $basePath, $pattern ] = $this->picoService->getRelativePath($this->website, $absolutePathPattern);

		if ($folder->isLocal()) {
			return parent::getFilesGlob($absolutePathPattern, $order);
		}

		try {
			$result = [];
			foreach (new GlobIterator($folder, $pattern) as $file) {
				$fileName = $file->getName();

				// exclude files ending with a ~ (vim/nano backup) or # (emacs backup)
				if (in_array($fileName[-1], [ '~', '#' ], true)) {
					continue;
				}

				$result[] = $basePath . $file->getPath();
			}

			return ($order === \Pico::SORT_DESC) ? array_reverse($result) : $result;
		} catch (\Exception $e) {
			return [];
		}
	}
}
