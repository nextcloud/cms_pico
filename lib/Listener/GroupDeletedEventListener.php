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

use OCA\CMSPico\Service\WebsitesService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;

class GroupDeletedEventListener implements IEventListener
{
	/** @var WebsitesService */
	private $websitesService;

	/**
	 * GroupDeletedEventListener constructor.
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
		if (!($event instanceof GroupDeletedEvent)) {
			return;
		}

		$groupId = $event->getGroup()->getGID();

		$limitGroups = $this->websitesService->getLimitGroups();
		$newLimitGroups = array_values(array_diff($limitGroups, [ $groupId ]));
		if ($newLimitGroups !== $limitGroups) {
			$this->websitesService->setLimitGroups($newLimitGroups);
		}

		foreach ($this->websitesService->getWebsites() as $website) {
			$groupAccess = $website->getGroupAccess();
			$newGroupAccess = array_values(array_diff($groupAccess, [ $groupId ]));
			if ($newGroupAccess !== $groupAccess) {
				$website->setGroupAccess($newGroupAccess);
				$this->websitesService->updateWebsite($website);
			}
		}
	}
}
