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

namespace OCA\CMSPico\Service;

use OCA\CMSPico\Exceptions\AssetInvalidPathException;
use OCA\CMSPico\Exceptions\AssetNotFoundException;
use OCA\CMSPico\Exceptions\AssetNotPermittedException;
use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Model\Website;
use OCP\Files\File;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class AssetService
{
	/** @var IRootFolder */
	private $rootFolder;

	/**
	 * @param IRootFolder $rootFolder
	 */
	function __construct(IRootFolder $rootFolder)
	{
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @param Website $website
	 *
	 * @return File
	 * @throws WebsiteNotFoundException
	 * @throws WebsiteNotPermittedException
	 * @throws AssetInvalidPathException
	 * @throws AssetNotFoundException
	 * @throws AssetNotPermittedException
	 */
	public function getAsset(Website $website) : File
	{
		try {
			$asset = $website->getPage();
			$asset = $website->normalizePath($asset, PicoService::DIR_ASSETS);

			$website->assertViewerAccess($asset);

			$userFolder = $this->rootFolder->getUserFolder($website->getUserId());

			/** @var File $node */
			$node = $userFolder->get($website->getPath() . $asset);

			if (!($node instanceof File)) {
				throw new AssetNotFoundException();
			}

			return $node;
		} catch (InvalidPathException $e) {
			throw new AssetInvalidPathException($e);
		} catch (NotFoundException $e) {
			throw new AssetNotFoundException($e);
		} catch (NotPermittedException $e) {
			throw new AssetNotPermittedException($e);
		}
	}
}
