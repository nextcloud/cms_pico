<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
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

namespace OCA\CMSPico\Model;

use OCA\CMSPico\Service\MiscService;
use OCP\Files\InvalidPathException;
use Pico;

class PicoPage
{
	/** @var MiscService */
	private $miscService;

	/** @var Website */
	private $website;

	/** @var Pico */
	private $pico;

	/** @var string */
	private $output;

	/**
	 * PicoPage constructor.
	 *
	 * @param Website $website
	 * @param Pico    $pico
	 * @param string  $output
	 */
	public function __construct(Website $website, Pico $pico, string $output)
	{
		$this->miscService = \OC::$server->query(MiscService::class);

		$this->website = $website;
		$this->pico = $pico;
		$this->output = $output;
	}

	/**
	 * @return string
	 */
	public function getAbsolutePath(): string
	{
		$requestFile = $this->pico->getRequestFile();
		if ($requestFile) {
			return $requestFile;
		}

		$contentDir = $this->pico->getConfig('content_dir');
		$contentExt = $this->pico->getConfig('content_ext');
		return $contentDir . ($this->website->getPage() ?: 'index') . $contentExt;
	}

	/**
	 * @return string
	 */
	public function getRelativePath(): string
	{
		$requestFile = $this->pico->getRequestFile();
		if ($requestFile) {
			try {
				$contentDir = $this->pico->getConfig('content_dir');
				$relativePath = $this->miscService->getRelativePath($requestFile, $contentDir);

				$contentExt = $this->pico->getConfig('content_ext');
				return $this->miscService->dropFileExtension($relativePath, $contentExt);
			} catch (InvalidPathException $e) {
				// fallback to the website's page
			}
		}

		return ($this->website->getPage() ?: 'index');
	}

	/**
	 * @return string
	 */
	public function getRawContent(): string
	{
		return $this->pico->getRawContent() ?? '';
	}

	/**
	 * @return array
	 */
	public function getMeta(): array
	{
		return $this->pico->getFileMeta() ?? [];
	}

	/**
	 * @return string
	 */
	public function getContent(): string
	{
		return $this->pico->getFileContent() ?? '';
	}

	/**
	 * @return string
	 */
	public function render(): string
	{
		return $this->output;
	}
}
