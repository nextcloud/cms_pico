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

use OCA\CMSPico\AppInfo\Application;

script(Application::APP_NAME, 'admin');
style(Application::APP_NAME, 'admin');

?>

<div class="section">
	<h2><?php p($l->t('Site Folders (Pico CMS)')) ?></h2>

	<table cellpadding="10" cellpadding="5">

		<tr>
			<td colspan="2">Choose the best way to link to your users' website. Copy one of the example below and
				paste the line in your Apache configuration
			</td>
		</tr>


		<tr class="lane">
			<td class="left">Using <i>mod_proxy</i>:<br/>
				<em><?php echo $_['nchost']; ?>/sites/example/</em>
			</td>
			<td class="right">
<pre>
ProxyPass /sites/ <?php echo $_['nchost']; ?>/index.php/apps/cms_pico/pico/
ProxyPassReverse /sites/ <?php echo \OC::$WEBROOT; ?>/index.php/apps/cms_pico/pico/
</pre>
			</td>
		</tr>


		<tr class="lane">
			<td class="left">Using <i>mod_rewrite</i>:<br/>
				<em><?php echo $_['nchost']; ?>/index.php/apps/cms_pico/pico/example/</em>
			</td>
			<td class="right">
<pre>
RewriteEngine On
RewriteRule /sites/(.*) <?php echo $_['nchost']; ?>/index.php/apps/cms_pico/pico/$1 [QSA,L]
</pre>
			</td>
		</tr>


		<tr class="lane">
			<td class="left">Using <i>mod_rewrite</i> and <i>mod_proxy</i>:<br/>
				<em><?php echo $_['nchost']; ?>/sites/example/</em>
			</td>
			<td class="right">
<pre>
RewriteEngine On
RewriteRule /sites/(.*) <?php echo $_['nchost']; ?>/index.php/apps/cms_pico/pico/$1 [P]
</pre>
			</td>
		</tr>

	</table>
</div>