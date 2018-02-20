<?php
/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\CMSPico\Settings;

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Compat\Section;
use OCP\IL10N;
use OCP\IURLGenerator;

class PersonalSection extends Section {

	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IL10N $l10n,
								IURLGenerator $urlGenerator) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getID() {
		return Application::APP_NAME;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return $this->l10n->t('Pico CMS');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority() {
		return 75;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIcon() {
		return $this->urlGenerator->imagePath(Application::APP_NAME, 'pico_cms.svg');
	}
}
