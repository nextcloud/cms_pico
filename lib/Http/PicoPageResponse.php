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

use OCA\CMSPico\Model\PicoPage;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\Http\Response;

class PicoPageResponse extends Response
{
	/** @var PicoPage */
	private $page;

	/**
	 * PicoPageResponse constructor.
	 *
	 * @param PicoPage $page
	 */
	public function __construct(PicoPage $page)
	{
		$this->page = $page;

		$this->addHeader('Content-Disposition', 'inline; filename=""');
		$this->setContentSecurityPolicy(new PicoContentSecurityPolicy());
	}

	/**
	 * @param EmptyContentSecurityPolicy $csp
	 *
	 * @return $this
	 */
	public function setContentSecurityPolicy(EmptyContentSecurityPolicy $csp) : self
	{
		if (!($csp instanceof PicoContentSecurityPolicy)) {
			// Pico really needs its own CSP...
			return $this;
		}

		parent::setContentSecurityPolicy($csp);
		return $this;
	}

	/**
	 * @return string
	 */
	public function render() : string
	{
		return $this->page->render();
	}
}
