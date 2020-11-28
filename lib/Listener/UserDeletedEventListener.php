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

namespace OCA\CMSPico\Listener;

use OCA\CMSPico\Service\WebsitesService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

class UserDeletedEventListener implements IEventListener
{
	/** @var WebsitesService */
	private $websitesService;

	/**
	 * UserDeletedEventListener constructor.
	 *
	 * @param WebsitesService $websitesService
	 */
	public function __construct(WebsitesService $websitesService)
	{
		$this->websitesService = $websitesService;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void
	{
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		$this->websitesService->deleteUserWebsites($event->getUser()->getUID());
	}
}
