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
 * Based on Pico dummy plugin:
 * @author  Daniel Rudolf
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
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
final class Nextcloud extends AbstractPicoPlugin {
	/**
	 * This plugin is enabled by default?
	 *
	 * @see AbstractPicoPlugin::$enabled
	 * @var boolean
	 */
	protected $enabled = true;

	/**
	 * This plugin depends on ...
	 *
	 * @see AbstractPicoPlugin::$dependsOn
	 * @var string[]
	 */
	protected $dependsOn = array();

	/** @var array */
	private $config;

	/** @var HTMLPurifier */
	private $htmlPurifier;

	/**
	 * Triggered after Pico has read its configuration
	 *
	 * @see    Pico::getConfig()
	 *
	 * @param  array &$config array of config variables
	 *
	 * @return void
	 */
	public function onConfigLoaded(array &$config) {
		$this->config = $config;
		$this->htmlPurifier = new HTMLPurifier(HTMLPurifier_Config::createDefault());

	}


	public function setEnabled($enabled, $recursive = true, $auto = false) {
		if ($enabled === false) {
			throw new RuntimeException('Nextcloud plugin cannot be disabled');
		}
	}


	/**
	 * Triggered after Pico has parsed the meta header
	 *
	 * @see    Pico::getFileMeta()
	 *
	 * @param  string[] &$meta parsed meta data
	 *
	 * @return void
	 */
	public function onMetaParsed(array &$meta) {
		$newMeta = [];
		foreach ($meta as $key => $value) {
			$newMeta[$key] = $this->htmlPurifier->purify($value);
		}

		$meta = $newMeta;
	}

	/**
	 * Triggered before Pico parses the pages content
	 *
	 * @see    Pico::prepareFileContent()
	 * @see    DummyPlugin::prepareFileContent()
	 * @see    DummyPlugin::onContentParsed()
	 *
	 * @param  string &$rawContent raw file contents
	 *
	 * @return void
	 */
	public function onContentParsing(&$rawContent) {
	}

	/**
	 * Triggered after Pico has prepared the raw file contents for parsing
	 *
	 * @see    Pico::parseFileContent()
	 * @see    DummyPlugin::onContentParsed()
	 *
	 * @param  string &$content prepared file contents for parsing
	 *
	 * @return void
	 */
	public function onContentPrepared(&$content) {
	}

	/**
	 * Triggered after Pico has parsed the contents of the file to serve
	 *
	 * @see    Pico::getFileContent()
	 *
	 * @param  string &$content parsed contents
	 *
	 * @return void
	 */
	public function onContentParsed(&$content) {
		$content = $this->htmlPurifier->purify($content);
	}

	/**
	 * Triggered before Pico reads all known pages
	 *
	 * @see    Pico::readPages()
	 * @see    DummyPlugin::onSinglePageLoaded()
	 * @see    DummyPlugin::onPagesLoaded()
	 * @return void
	 */
	public function onPagesLoading() {
	}

	/**
	 * Triggered when Pico reads a single page from the list of all known pages
	 *
	 * The `$pageData` parameter consists of the following values:
	 *
	 * | Array key      | Type   | Description                              |
	 * | -------------- | ------ | ---------------------------------------- |
	 * | id             | string | relative path to the content file        |
	 * | url            | string | URL to the page                          |
	 * | title          | string | title of the page (YAML header)          |
	 * | description    | string | description of the page (YAML header)    |
	 * | author         | string | author of the page (YAML header)         |
	 * | time           | string | timestamp derived from the Date header   |
	 * | date           | string | date of the page (YAML header)           |
	 * | date_formatted | string | formatted date of the page               |
	 * | raw_content    | string | raw, not yet parsed contents of the page |
	 * | meta           | string | parsed meta data of the page             |
	 *
	 * @see    DummyPlugin::onPagesLoaded()
	 *
	 * @param  array &$pageData data of the loaded page
	 *
	 * @return void
	 */
	public function onSinglePageLoaded(array &$pageData) {
	}

	/**
	 * Triggered after Pico has read all known pages
	 *
	 * See {@link DummyPlugin::onSinglePageLoaded()} for details about the
	 * structure of the page data.
	 *
	 * @see    Pico::getPages()
	 * @see    Pico::getCurrentPage()
	 * @see    Pico::getPreviousPage()
	 * @see    Pico::getNextPage()
	 *
	 * @param  array[] &$pages data of all known pages
	 * @param  array|null &$currentPage data of the page being served
	 * @param  array|null &$previousPage data of the previous page
	 * @param  array|null &$nextPage data of the next page
	 *
	 * @return void
	 */
	public function onPagesLoaded(
		array &$pages,
		array &$currentPage = null,
		array &$previousPage = null,
		array &$nextPage = null
	) {
	}

	/**
	 * Triggered before Pico registers the twig template engine
	 *
	 * @return void
	 */
	public function onTwigRegistration() {
	}

	/**
	 * Triggered before Pico renders the page
	 *
	 * @see    Pico::getTwig()
	 * @see    DummyPlugin::onPageRendered()
	 *
	 * @param  Twig_Environment &$twig twig template engine
	 * @param  array &$twigVariables template variables
	 * @param  string &$templateName file name of the template
	 *
	 * @return void
	 */
	public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName) {
		$twigVariables['theme_url'] = '/apps/cms_pico/Pico/themes/' . $this->config['theme'];
	}

	/**
	 * Triggered after Pico has rendered the page
	 *
	 * @param  string &$output contents which will be sent to the user
	 *
	 * @return void
	 */
	public function onPageRendered(&$output) {
	}
}
