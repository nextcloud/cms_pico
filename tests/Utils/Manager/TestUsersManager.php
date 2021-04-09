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

namespace OCA\CMSPico\Tests\Utils\Manager;

use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class TestUsersManager extends TestManager
{
	/** @var IUserManager */
	protected $userManager;

	/** @var IUserSession */
	protected $userSession;

	/** @var string|null */
	protected $userNamePrefix;

	/** @var IUser[] */
	protected $users = [];

	public function setUp(): void
	{
		$this->userManager = \OC::$server->getUserManager();
		$this->userSession = \OC::$server->getUserSession();
	}

	public function tearDown(): void
	{
		$currentUserId = $this->userSession->isLoggedIn() ? $this->userSession->getUser()->getUID() : null;
		foreach ($this->users as $user) {
			if ($user->getUID() === $currentUserId) {
				$this->userSession->setUser(null);
			}

			$user->delete();
		}

		$this->users = [];
	}

	public function getTestUser(string $id): IUser
	{
		$uid = $this->getTestUserPrefix() . $id;

		$user = $this->userManager->get($uid);
		if ($user === null) {
			$user = $this->userManager->createUser($uid, $uid);
			if ($user === false) {
				throw new \RuntimeException(sprintf('Could not create test user "%s"', $uid));
			}
		}

		$this->users[$id] = $user;
		return $user;
	}

	public function loginTestUser(string $id): void
	{
		$user = $this->getTestUser($id);
		$this->userSession->setUser($user);
	}

	public function getCurrentUser(): ?IUser
	{
		return $this->userSession->getUser();
	}

	public function loginUser(string $uid): void
	{
		$user = $this->userManager->get($uid);
		if ($user === null) {
			throw new \RuntimeException(sprintf('User "%s" not found', $uid));
		}

		$this->userSession->setUser($user);
	}

	public function logoutUser(): void
	{
		$this->userSession->setUser(null);
	}

	public function runAsUser(string $uid, callable $callback)
	{
		$currentUser = $this->getCurrentUser();
		$this->loginUser($uid);

		$result = $callback();

		if ($currentUser !== null) {
			$this->loginUser($currentUser->getUID());
		} else {
			$this->logoutUser();
		}

		return $result;
	}

	protected function getTestUserPrefix(): string
	{
		if ($this->userNamePrefix === null) {
			$testCaseHash = substr(hash('sha256', $this->testCaseName), 0, 16);
			$this->userNamePrefix = 'pico-test-' . $testCaseHash . '_';
		}

		return $this->userNamePrefix;
	}
}
