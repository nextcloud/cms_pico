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

namespace OCA\CMSPico\ExternalStorage;

use OCA\Files_External\Lib\Backend\Backend as CommonBackend;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Service\BackendService;
use OCP\IL10N;

class Backend extends CommonBackend
{
	public function __construct(IL10N $l10n)
	{
		$this
			->setIdentifier('local_unencrypted')
			->setStorageClass('\OCA\CMSPico\ExternalStorage\Storage')
			->setAllowedVisibility(BackendService::VISIBILITY_ADMIN)
			->setText($l10n->t('Local (unencrypted)'))
			->addParameters([
				new DefinitionParameter('datadir', $l10n->t('Location')),
			]);
	}
}
