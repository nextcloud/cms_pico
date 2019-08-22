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

use OCP\AppFramework\Http\EmptyContentSecurityPolicy;

class PicoContentSecurityPolicy extends EmptyContentSecurityPolicy
{
	/** @var bool Whether inline JS snippets are allowed */
	protected $inlineScriptAllowed = true;

	/** @var bool Whether eval in JS scripts is allowed */
	protected $evalScriptAllowed = true;

	/** @var array Domains from which scripts can get loaded */
	protected $allowedScriptDomains = [
		'\'self\'',
	];

	/** @var bool Whether inline CSS is allowed */
	protected $inlineStyleAllowed = true;

	/** @var array Domains from which CSS can get loaded */
	protected $allowedStyleDomains = [
		'\'self\'',
	];

	/** @var array Domains from which images can get loaded */
	protected $allowedImageDomains = [
		'\'self\'',
		'data:',
		'blob:',
	];

	/** @var array Domains to which connections can be done */
	protected $allowedConnectDomains = [
		'\'self\'',
	];

	/** @var array Domains from which media elements can be loaded */
	protected $allowedMediaDomains = [
		'\'self\'',
	];

	/** @var array Domains from which object elements can be loaded */
	protected $allowedObjectDomains = [
		'\'self\'',
	];

	/** @var array Domains from which iframes can be loaded */
	protected $allowedFrameDomains = [];

	/** @var array Domains from which fonts can be loaded */
	protected $allowedFontDomains = [
		'\'self\'',
		'data:',
	];

	/** @var array Domains from which web-workers and nested browsing content can load elements */
	protected $allowedChildSrcDomains = [];

	/** @var array Domains which can embed this Nextcloud instance */
	protected $allowedFrameAncestors = [];
}
