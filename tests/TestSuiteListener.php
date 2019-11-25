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

namespace OCA\CMSPico\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\BaseTestListener;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;

class Env extends BaseTestListener implements TestListener
{
	const ENV_TEST_USER1 = 'testpico1';
	const ENV_TEST_USER2 = 'testpico2';
	const ENV_TEST_USER3 = 'testpico3';

	/** @var array<string> */
	private $users;

	public function startTestSuite(TestSuite $suite)
	{
		$userManager = \OC::$server->getUserManager();
		$this->users = self::listUsers();

		foreach ($this->users AS $uid) {
			if ($userManager->userExists($uid) === false) {
				$userManager->createUser($uid, $uid);
			}
		}
	}

	public function endTestSuite(TestSuite $suite)
	{
		foreach ($this->users AS $uid) {
			$user = \OC::$server->getUserManager()->get($uid);
			if ($user !== null) {
				$user->delete();
			}
		}
	}

	public static function setUser($which)
	{
		$user = \OC::$server->getUserManager()->get($which);

		$userSession = \OC::$server->getUserSession();
		$userSession->setUser($user);

		return $user->getUID();
	}

	public static function currentUser()
	{
		$userSession = \OC::$server->getUserSession();
		return $userSession->getUser()->getUID();
	}

	public static function logout()
	{
		$userSession = \OC::$server->getUserSession();
		$userSession->setUser(null);
	}

	public static function listUsers()
	{
		return [
			self::ENV_TEST_USER1,
			self::ENV_TEST_USER2,
			self::ENV_TEST_USER3
		];
	}
}
