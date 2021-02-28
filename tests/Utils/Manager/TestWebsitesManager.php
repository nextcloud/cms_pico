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

use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Model\WebsiteCore;
use OCA\CMSPico\Service\WebsitesService;

class TestWebsitesManager extends TestManager
{
	/** @var WebsitesService */
	protected $websitesService;

	/** @var TestUsersManager */
	protected $testUsersManager;

	/** @var Website[] */
	protected $websites = [];

	/** @var array */
	protected $defaultWebsiteData = [
		'user_id'  => '{user_id}',
		'site'     => '{site}',
		'name'     => 'Pico Test Website "{id}" ({site})',
		'type'     => WebsiteCore::TYPE_PUBLIC,
		'path'     => '/Pico Test Website {site}/',
		'theme'    => 'default',
		'options'  => [],
		'template' => 'sample_pico',
	];

	public function setUp(): void
	{
		$this->websitesService = \OC::$server->query(WebsitesService::class);

		$this->testUsersManager = TestUsersManager::getInstance($this->testCaseName);
	}

	public function tearDown(): void
	{
		foreach ($this->websites as $website) {
			$this->websitesService->deleteWebsite($website);
		}

		$this->websites = [];
	}

	public function createTestWebsiteData(string $id, array $data = []): array
	{
		$user = $this->testUsersManager->getCurrentUser() ?? $this->testUsersManager->getTestUser('user');
		$site = substr(hash('sha256', $this->testCaseName . '_' . $id), 0, 16);

		$templateData = [ '{id}' => $id, '{user_id}' => $user->getUID(), '{site}' => $site ];
		foreach ($this->defaultWebsiteData as $key => $defaultValue) {
			$value = $data[$key] ?? $defaultValue;
			$data[$key] = is_string($value) ? strtr($value, $templateData) : $value;
			$templateData['{' . $key . '}'] = $data[$key];
		}

		return $data;
	}

	public function compareTestWebsiteData(array $data1, array $data2): bool
	{
		return (
			($data1['user_id'] === $data2['user_id'])
			&& ($data1['site'] === $data2['site'])
			&& ($data1['name'] === $data2['name'])
			&& ($data1['type'] === $data2['type'])
			&& ($data1['path'] === $data2['path'])
			&& ($data1['theme'] === $data2['theme'])
		);
	}

	public function createTestWebsite(string $id, array $data = []): Website
	{
		$data = $this->createTestWebsiteData($id, $data);

		if (isset($this->websites[$id])) {
			$originalWebsite = $this->websites[$id];
			if (!$this->compareTestWebsiteData($data, $originalWebsite->getData())) {
				throw new \RuntimeException(sprintf('Unable to create test website "%s": Website exists', $id));
			}

			return $originalWebsite;
		}

		$website = new Website($data);
		$templateName = $data['template'] ?? '';

		$this->testUsersManager->runAsUser($data['user_id'], function () use ($website, $templateName) {
			$this->websitesService->createWebsite($website, $templateName);
		});

		$this->websites[$id] = $website;
		return $website;
	}

	public function deleteTestWebsite(string $id): void
	{
		$website = $this->websites[$id];
		$this->websitesService->deleteWebsite($website);

		unset($this->websites[$id]);
	}

	public function addTestWebsite(string $id, Website $website): void
	{
		$this->websites[$id] = $website;
	}

	public function removeTestWebsite(string $id): void
	{
		unset($this->websites[$id]);
	}

	public function getTestWebsite(string $id): Website
	{
		if (!isset($this->websites[$id])) {
			throw new \RuntimeException(sprintf('Unable to return test website "%s": Website not found', $id));
		}

		return $this->websites[$id];
	}
}
