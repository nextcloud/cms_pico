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

namespace OCA\CMSPico\Exceptions;

class WebsiteInvalidOwnerException extends \Exception
{
	/** @var string|null */
	private $site;

	/**
	 * WebsiteInvalidOwnerException constructor.
	 *
	 * @param string|null     $site
	 * @param \Exception|null $previous
	 */
	public function __construct(string $site = null, \Exception $previous = null)
	{
		$this->site = $site;

		$message = '';
		if ($site) {
			$message = sprintf("Unable to load website '%s': Website owner is not allowed to create websites", $site);
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
}
