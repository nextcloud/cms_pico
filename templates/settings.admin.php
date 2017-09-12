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

script(
	Application::APP_NAME,
	['admin.result', 'admin.navigation', 'admin.elements', 'admin']
);

style(Application::APP_NAME, 'admin');

?>

<div class="section" style="margin: 50px 0px 50px 0px;">
	<h2><?php p($l->t('Site Folders (Pico CMS)')) ?></h2>


	<table cellpadding="10" cellpadding="5">

		<tr>
			<td colspan="2" class="title"><?php p($l->t('Your Apache configuration')); ?></td>
		</tr>

		<tr>
			<td colspan="2"><?php p(
					$l->t(
						"Choose the best way to link to your users' website. Copy one of the example below and paste the line in your Apache configuration"
					)
				); ?>
			</td>
		</tr>


		<tr class="lane">
			<td class="left"><?php p($l->t('Using MOD_PROXY:')); ?><br />
				<em><?php p($_['nchost']); ?>/sites/example/</em>
			</td>
			<td class="right">
<pre>
ProxyPass /sites/ <?php p($_['nchost']); ?>/index.php/apps/cms_pico/pico/
ProxyPassReverse /sites/ <?php p($_['nchost']); ?>/index.php/apps/cms_pico/pico/
</pre>
			</td>
		</tr>


		<tr class="lane">
			<td class="left"><?php p($l->t('Using MOD_REWRITE:')); ?><br />
				<em><?php p($_['nchost']); ?>/index.php/apps/cms_pico/pico/example/</em>
			</td>
			<td class="right">
<pre>
RewriteEngine On
RewriteRule /sites/(.*) <?php p($_['nchost']); ?>/index.php/apps/cms_pico/pico/$1 [QSA,L]
</pre>
			</td>
		</tr>


		<tr class="lane">
			<td class="left"><?php p($l->t('Using MOD_REWRITE and MOD_PROXY:')); ?><br />
				<em><?php p($_['nchost']); ?>/sites/example/</em>
			</td>
			<td class="right">
<pre>
RewriteEngine On
RewriteRule /sites/(.*) <?php p($_['nchost']); ?>/index.php/apps/cms_pico/pico/$1 [P]
</pre>
			</td>
		</tr>


		<tr>
			<td>&nbsp;</td>
		</tr>

		<tr>
			<td colspan="2" class="title"><?php p($l->t('Adding a template')); ?></td>
		</tr>

		<tr>
			<td colspan="2"><?php p(
					$l->t(
						'To add a new template, you will need to create a new folder in apps/cms_pico/templates/'
					)
				); ?>
				<i><?php echo $_['templates_dir']; ?></i>
				<br/>
				<?php p(
					$l->t(
						'Please use the sample_pico base structure: your template will need the config/ folder to be identical.'
					)
				); ?>
			</td>
		</tr>

		<tr class="lane">
			<td class="left"><?php p($l->t('Select a new template:')); ?><br />
				<em>Refresh the page if you cannot find your new folder</em>
			</td>
			<td class="right">
				<select id="admin_cms_pico_new_template" class="field250">
					<?php
					for($i = 0; $i < sizeof($_['templates_new']); $i++)
					{
						echo '<option value="' . $i . '">' . $_['templates_new'][$i] . '</option>';
					}
					?>
				</select>

			</td>
		</tr>

	</table>
</div>