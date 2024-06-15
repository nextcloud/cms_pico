<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2020, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
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

use OCA\CMSPico\Exceptions\WebsiteInvalidFilesystemException;
use OCA\CMSPico\Exceptions\WebsiteNotPermittedException;
use OCA\CMSPico\Files\StorageFile;
use OCA\CMSPico\Files\StorageFolder;
use OCA\CMSPico\Service\MiscService;
use OCP\Files\Folder as OCFolder;
use OCP\Files\InvalidPathException;
use OCP\Files\Node as OCNode;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IGroupManager;

class WebsiteRequest
{
	/** @var Website */
	private $website;

	/** @var string|null */
	private $viewer;

	/** @var string */
	private $page;

	/** @var bool */
	private $proxyRequest;

	/** @var IGroupManager */
	private $groupManager;

	/** @var MiscService */
	private $miscService;

	/**
	 * WebsiteRequest constructor.
	 *
	 * @param Website     $website
	 * @param string|null $viewer
	 * @param string      $page
	 * @param bool        $proxyRequest
	 */
	public function __construct(Website $website, string $viewer = null, string $page = '', bool $proxyRequest = false)
	{
		$this->groupManager = \OC::$server->getGroupManager();
		$this->miscService = \OC::$server->query(MiscService::class);

		$this->website = $website;
		$this->viewer = $viewer;
		$this->page = $page;
		$this->proxyRequest = $proxyRequest;

		if ($this->viewer === null) {
			$userSession = \OC::$server->getUserSession();
			$this->viewer = $userSession->isLoggedIn() ? $userSession->getUser()->getUID() : null;
		}
	}

	/**
	 * @param string $path
	 * @param array  $meta
	 *
	 * @throws InvalidPathException
	 * @throws WebsiteInvalidFilesystemException
	 * @throws WebsiteNotPermittedException
	 * @throws NotPermittedException
	 */
	public function assertViewerAccess(string $path, array $meta = []): void
	{
		if ($this->website->getType() === Website::TYPE_PUBLIC) {
			if (empty($meta['access'])) {
				return;
			}

			$groupPageAccess = $meta['access'];
			if (!is_array($groupPageAccess)) {
				$groupPageAccess = explode(',', $groupPageAccess);
			}

			foreach ($groupPageAccess as $group) {
				$group = trim($group);

				if ($group === 'public') {
					return;
				} elseif ($group === 'private') {
					continue;
				}

				if ($this->getViewer() && $this->groupManager->groupExists($group)) {
					if ($this->groupManager->isInGroup($this->getViewer(), $group)) {
						return;
					}
				}
			}
		}

		if ($this->getViewer()) {
			if ($this->getViewer() === $this->website->getUserId()) {
				return;
			}

			$groupAccess = $this->website->getGroupAccess();
			foreach ($groupAccess as $group) {
				if ($this->groupManager->groupExists($group)) {
					if ($this->groupManager->isInGroup($this->getViewer(), $group)) {
						return;
					}
				}
			}

			/** @var OCFolder $viewerOCFolder */
			$viewerOCFolder = \OC::$server->getUserFolder($this->getViewer());
			$viewerAccessClosure = function (OCNode $node) use ($viewerOCFolder) {
				$nodeId = $node->getId();

				$viewerNodes = $viewerOCFolder->getById($nodeId);
				foreach ($viewerNodes as $viewerNode) {
					if ($viewerNode->isReadable()) {
						return true;
					}
				}

				return false;
			};

			$websiteFolder = $this->website->getWebsiteFolder();

			$path = $this->miscService->normalizePath($path);
			while ($path && ($path !== '.')) {
				try {
					/** @var StorageFile|StorageFolder $file */
					$file = $websiteFolder->get($path);
				} catch (NotFoundException $e) {
					$file = null;
				}

				if ($file) {
					if ($viewerAccessClosure($file->getOCNode())) {
						return;
					}

					if ($this->website->getType() === Website::TYPE_PRIVATE) {
						throw new WebsiteNotPermittedException($this->getWebsite()->getSite());
					}

					throw new NotPermittedException();
				}

				$path = dirname($path);
			}

			if ($viewerAccessClosure($websiteFolder->getOCNode())) {
				return;
			}
		}

		if ($this->website->getType() === Website::TYPE_PRIVATE) {
			throw new WebsiteNotPermittedException($this->getWebsite()->getSite());
		}

		throw new NotPermittedException();
	}

	/**
	 * @return Website
	 */
	public function getWebsite(): Website
	{
		return $this->website;
	}

	/**
	 * @return string|null
	 */
	public function getViewer(): ?string
	{
		return $this->viewer;
	}

	/**
	 * @return string
	 */
	public function getPage(): string
	{
		return $this->page;
	}

	/**
	 * @return bool
	 */
	public function isProxyRequest(): bool
	{
		return $this->proxyRequest;
	}
}
