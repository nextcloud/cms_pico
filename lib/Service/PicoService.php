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

namespace OCA\CMSPico\Service;

use OCA\CMSPico\Model\Website;
use Pico;

class PicoService {

	const DIR_CONFIG = 'config/';
	const DIR_PLUGINS = 'plugins/';
	const DIR_THEMES = 'themes/';

	private $userId;
	/** @var MiscService */
	private $miscService;

	/**
	 * PicoService constructor.
	 *
	 * @param string $userId
	 * @param MiscService $miscService
	 */
	function __construct($userId, MiscService $miscService) {
		$this->userId = $userId;
		$this->miscService = $miscService;
	}


	/**
	 * @param Website $website
	 *
	 * @return string
	 */
	public function getContent(Website $website) {

		$pico = new Pico(
			$website->getAbsolutePath(),
			self::DIR_CONFIG, self::DIR_PLUGINS, self::DIR_THEMES
		);

		$pico->run();

		$absolutePath = $this->getAbsolutePathFromPage($pico);
		$website->contentMustBeLocal($absolutePath);
		$website->viewerMustHaveAccess($absolutePath, $pico->getFileMeta());

		return $pico->getFileContent();
	}


	/**
	 * @param Pico $pico
	 *
	 * @return string
	 */
	private function getAbsolutePathFromPage(Pico $pico) {
		return $pico->getConfig()['content_dir'] . $pico->getCurrentPage()['id'] . '.md';
	}

}