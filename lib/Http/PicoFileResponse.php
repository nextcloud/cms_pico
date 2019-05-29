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

namespace OCA\CMSPico\Http;

use OCP\AppFramework\Http\DownloadResponse;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class PicoFileResponse extends DownloadResponse
{
	/** @var File */
	private $file;

	/** @var array<string,int> */
	private $cacheFor = [
		'application/font-sfnt' => 2592000,
		'application/font-woff' => 2592000,
		'application/javascript' => 604800,
		'application/json' => 604800,
		'application/vnd.ms-fontobject' => 2592000,
		'image/bmp' => 2592000,
		'image/gif' => 2592000,
		'image/jpeg' => 2592000,
		'image/png' => 2592000,
		'image/svg+xml' => 2592000,
		'image/tiff' => 2592000,
		'image/vnd.microsoft.icon' => 2592000,
		'image/webp' => 2592000,
		'image/x-icon' => 2592000,
		'text/css' => 604800,
	];

	/**
	 * PicoFileResponse constructor.
	 *
	 * @param File        $file
	 * @param string|null $secureMimeType
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function __construct(File $file, string $secureMimeType = null)
	{
		$this->file = $file;

		parent::setContentSecurityPolicy(new PicoContentSecurityPolicy());

		try {
			$etag = $file->getEtag();
			$lastModified = $file->getMTime();
			$mimeType = $file->getMimeType();

			if (($file->getPermissions() & Constants::PERMISSION_READ) !== Constants::PERMISSION_READ) {
				throw new NotPermittedException();
			}
		} catch (InvalidPathException $e) {
			throw new NotFoundException();
		}

		$contentType = $secureMimeType ?: $mimeType ?: 'application/octet-stream';
		parent::__construct($file->getName(), $contentType);

		$this->setETag($etag);

		if (isset($this->cacheFor[$mimeType])) {
			$this->cacheFor($this->cacheFor[$mimeType]);
		}

		try {
			$this->setLastModified((new \DateTime())->setTimestamp($lastModified));
		} catch (\Exception $e) {
			// ignore DateTime exception
		}
	}

	/**
	 * @param EmptyContentSecurityPolicy $csp
	 *
	 * @return $this
	 */
	public function setContentSecurityPolicy(EmptyContentSecurityPolicy $csp): self
	{
		if (!($csp instanceof PicoContentSecurityPolicy)) {
			// Pico really needs its own CSP...
			return $this;
		}

		parent::setContentSecurityPolicy($csp);
		return $this;
	}

	/**
	 * @param int $cacheSeconds
	 *
	 * @return $this
	 */
	public function cacheFor(int $cacheSeconds): self
	{
		if($cacheSeconds > 0) {
			$this->addHeader('Cache-Control', 'max-age=' . $cacheSeconds . ', public');
			$this->addHeader('Pragma', 'public');

			try {
				$expires = new \DateTime();
				$expires->add(new \DateInterval('PT' . $cacheSeconds . 'S'));
				$this->addHeader('Expires', $expires->format(\DateTime::RFC2822));
			} catch (\Exception $e) {
				// ignore DateTime and DateInterval exceptions
			}
		} else {
			$this->addHeader('Cache-Control', 'no-cache, must-revalidate');
			$this->addHeader('Pragma', null);
			$this->addHeader('Expires', null);
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function noCache(): self
	{
		$this->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
		$this->addHeader('Pragma', null);
		$this->addHeader('Expires', null);

		return $this;
	}

	/**
	 * @return string
	 */
	public function render(): string
	{
		try {
			return $this->file->getContent();
		} catch (NotPermittedException $e) {
			// we checked permissions in constructor, silently ignore
			return '';
		}
	}
}
