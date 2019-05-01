<?php

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

	/** @var array Domains from which web-workers can be loaded */
	protected $allowedWorkerSrcDomains = [];

	/** @var array Locations to report violations to */
	protected $reportTo = [];
}
