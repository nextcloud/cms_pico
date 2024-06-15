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
use OCA\CMSPico\Exceptions\WebsiteInvalidFilesystemException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Files\StorageFile;
use OCA\CMSPico\Files\StorageFolder;
use OCA\CMSPico\Model\PicoAsset;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Model\WebsiteRequest;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class AssetsService
{
	/**
	 * @param WebsiteRequest $websiteRequest
	 *
	 * @return PicoAsset
	 * @throws WebsiteInvalidFilesystemException
	 * @throws WebsiteNotPermittedException
	 * @throws AssetInvalidPathException
	 * @throws AssetNotFoundException
	 * @throws AssetNotPermittedException
	 */
	public function getAsset(WebsiteRequest $websiteRequest): PicoAsset
	{
		$website = $websiteRequest->getWebsite();
		$asset = $websiteRequest->getPage();

		try {
			$assetsDir = PicoService::DIR_ASSETS . '/';
			$assetsDirLength = strlen($assetsDir);
			if (substr_compare($asset, $assetsDir, 0, $assetsDirLength) !== 0) {
				throw new InvalidPathException();
			}

			$websiteRequest->assertViewerAccess($asset);

			$asset = substr($asset, $assetsDirLength);
			if ($asset === '') {
				throw new InvalidPathException();
			}

			/** @var StorageFile $assetFile */
			$assetFile = $this->getAssetsFolder($website)->getFile($asset);
			$picoAsset = new PicoAsset($assetFile, $website->getType() === Website::TYPE_PUBLIC);
		} catch (InvalidPathException $e) {
			throw new AssetInvalidPathException($website->getSite(), $asset, $e);
		} catch (NotFoundException $e) {
			throw new AssetNotFoundException($website->getSite(), $asset, $e);
		} catch (NotPermittedException $e) {
			throw new AssetNotPermittedException($website->getSite(), $asset, $e);
		}

		return $picoAsset;
	}

	/**
	 * @param Website $website
	 *
	 * @return StorageFolder
	 * @throws WebsiteInvalidFilesystemException
	 */
	public function getAssetsFolder(Website $website): StorageFolder
	{
		try {
			return $website->getWebsiteFolder()->getFolder(PicoService::DIR_ASSETS)->fakeRoot();
		} catch (InvalidPathException | NotFoundException $e) {
			throw new WebsiteInvalidFilesystemException($website->getSite(), $e);
		}
	}

	/**
	 * @param Website $website
	 *
	 * @return string
	 * @throws WebsiteInvalidFilesystemException
	 */
	public function getAssetsPath(Website $website): string
	{
		try {
			return $this->getAssetsFolder($website)->getLocalPath() . '/';
		} catch (InvalidPathException | NotFoundException $e) {
			throw new WebsiteInvalidFilesystemException($website->getSite(), $e);
		}
	}

	/**
	 * @param WebsiteRequest $websiteRequest
	 *
	 * @return string
	 * @throws WebsiteInvalidFilesystemException
	 */
	public function getAssetsUrl(WebsiteRequest $websiteRequest): string
	{
		$website = $websiteRequest->getWebsite();
		$websiteUrl = $website->getWebsiteUrl($websiteRequest->isProxyRequest());
		$assetsETag = $this->getAssetsFolder($websiteRequest->getWebsite())->getOCNode()->getEtag();
		return $websiteUrl . PicoService::DIR_ASSETS . '-' . $assetsETag . '/';
	}
}
