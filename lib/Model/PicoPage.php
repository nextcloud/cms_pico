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

use OCA\CMSPico\Exceptions\PageInvalidPathException;
use OCA\CMSPico\Service\PicoService;
use Pico;

class PicoPage
{
	/** @var Pico */
	private $pico;

	/** @var Website */
	private $website;

	/** @var string */
	private $output;

	public function __construct(Website $website, Pico $pico, string $output)
	{
		$this->website = $website;
		$this->pico = $pico;
		$this->output = $output;
	}

	/**
	 * @return string
	 */
	public function getAbsolutePath() : string
	{
		$absolutePath = $this->pico->getRequestFile();
		if ($absolutePath) {
			return $absolutePath;
		}

		return $this->website->getAbsolutePath(PicoService::DIR_CONTENT . '/' . $this->website->getPage());
	}

	/**
	 * @return string
	 */
	public function getRelativePath() : string
	{
		$absolutePath = $this->pico->getRequestFile();
		if ($absolutePath) {
			try {
				return $this->website->getRelativePagePath($absolutePath);
			} catch (PageInvalidPathException $e) {
				// silently ignore this exception, proceed
			}
		}

		return $this->website->getPage();
	}

	/**
	 * @return string
	 */
	public function getRawContent() : string
	{
		return $this->pico->getRawContent();
	}

	/**
	 * @return array
	 */
	public function getMeta() : array
	{
		return $this->pico->getFileMeta();
	}

	/**
	 * @return string
	 */
	public function getContent() : string
	{
		return $this->pico->getFileContent();
	}

	/**
	 * @return string
	 */
	public function render() : string
	{
		return $this->output;
	}
}
