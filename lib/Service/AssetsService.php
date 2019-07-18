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

/**
 * @TODO In den "virtuellen" assets-Pfad sollte man optional den Etag des assets-Verzeichnisses (assets-<hash>)
 *       einbauen können. Grundsätzlich gehen beide URLs, wenn der Etag aber im Verzeichnisnamen enthalten ist wird
 *       bei der Antwort cacheFor() mit sehr großen Zahlen verwendet. Damit man die in Pico aber überhaupt nutzen kann
 *       braucht's ein %asset_dir% und {{ asset_dir }}. Sollte auch zu Pico 2.0.5-6 backported werden...
 */
class AssetsService
{
	/** @var IRootFolder */
	private $rootFolder;

	/** @var MiscService */
	private $miscService;

	/**
	 * AssetsService constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param MiscService $miscService
	 */
	public function __construct(IRootFolder $rootFolder, MiscService $miscService)
	{
		$this->rootFolder = $rootFolder;
		$this->miscService = $miscService;
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
	public function getAsset(Website $website): File
	{
		try {
			$asset = $website->getPage();
			$asset = $this->miscService->normalizePath($asset);
			if (substr($asset, 0, strlen(PicoService::DIR_ASSETS . '/')) !== PicoService::DIR_ASSETS . '/') {
				throw new InvalidPathException();
			}

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
