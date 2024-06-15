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

class AssetNotFoundException extends \Exception
{
	/** @var string|null */
	private $site;

	/** @var string|null */
	private $asset;

	/**
	 * AssetNotFoundException constructor.
	 *
	 * @param string|null     $site
	 * @param string|null     $asset
	 * @param \Exception|null $previous
	 */
	public function __construct(string $site = null, string $asset = null, \Exception $previous = null)
	{
		$this->site = $site;
		$this->asset = $asset;

		$message = '';
		if ($site && $asset) {
			$message = sprintf("Unable to access asset '%s' of website '%s': No such asset", $asset, $site);
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
	public function getAsset(): ?string
	{
		return $this->asset;
	}
}
