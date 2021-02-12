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

namespace OCA\CMSPico\Listener;

use OCA\CMSPico\ExternalStorage\BackendProvider;
use OCA\CMSPico\Service\WebsitesService;
use OCA\Files_External\Service\BackendService;
use OCP\Encryption\IManager as EncryptionManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class ExternalStorageBackendEventListener implements IEventListener
{
	/** @var EncryptionManager */
	private $encryptionManager;

	/**
	 * ExternalStorageBackendEventListener constructor.
	 *
	 * @param EncryptionManager $encryptionManager
	 */
	public function __construct(EncryptionManager $encryptionManager)
	{
		$this->encryptionManager = $encryptionManager;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void
	{
		// OCA\Files_External::loadAdditionalBackends dispatches a GenericEvent, thus we can't check the event type
		// this event won't ever be dispatched if OCA\Files_External isn't installed and enabled

		if ($this->encryptionManager->isEnabled()) {
			/** @var BackendService $backendService */
			$backendService = \OC::$server->query(BackendService::class);
			$backendService->registerBackendProvider(new BackendProvider());
		}
	}
}
