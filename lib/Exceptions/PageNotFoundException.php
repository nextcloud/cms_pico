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

namespace OCA\CMSPico\Exceptions;

class PageNotFoundException extends \Exception
{
	/** @var string|null */
	private $site;

	/** @var string|null */
	private $page;

	/**
	 * PageNotFoundException constructor.
	 *
	 * @param string|null     $site
	 * @param string|null     $page
	 * @param \Exception|null $previous
	 */
	public function __construct(string $site = null, string $page = null, \Exception $previous = null)
	{
		$this->site = $site;
		$this->page = $page;

		$message = '';
		if ($site && $page) {
			$message = sprintf("Unable to access page '%s' of website '%s': No such page", $page, $site);
		} elseif ($previous) {
			$message = $previous->getMessage();
		}

		if ($previous) {
			parent::__construct($message, $previous->getCode(), $previous);
		} else {
			parent::__construct($message);
		}
	}

	/**
	 * @return string|null
	 */
	public function getSite(): ?string
	{
		return $this->site;
	}

	/**
	 * @return string|null
	 */
	public function getPage(): ?string
	{
		return $this->page;
	}
}
