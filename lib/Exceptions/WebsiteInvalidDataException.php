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

class WebsiteInvalidDataException extends \Exception
{
	/** @var string|null */
	private $site;

	/** @var string|null */
	private $field;

	/** @var string|null */
	private $error;

	/**
	 * WebsiteInvalidDataException constructor.
	 *
	 * @param string|null     $site
	 * @param string|null     $field
	 * @param string|null     $error
	 * @param \Throwable|null $previous
	 */
	public function __construct(
		string $site = null,
		string $field = null,
		string $error = null,
		\Throwable $previous = null
	) {
		$this->site = $site;
		$this->field = $field;
		$this->error = $error;

		$message = '';
		if ($site && $field && $error) {
			$message = sprintf("Unable to modify website '%s': Invalid data given for '%s': %s", $site, $field, $error);
		} elseif ($site && $field) {
			$message = sprintf("Unable to modify website '%s': Invalid data given for '%s'", $site, $field);
		} elseif ($site) {
			$message = sprintf("Unable to modify website '%s': Invalid data given", $site);
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
	public function getField(): ?string
	{
		return $this->field;
	}

	/**
	 * @return string|null
	 */
	public function getError(): ?string
	{
		return $this->error;
	}
}
