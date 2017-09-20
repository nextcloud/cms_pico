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


script(Application::APP_NAME, 'vendor/notyf');
style(Application::APP_NAME, 'notyf');

script(
	Application::APP_NAME,
	['personal.result', 'personal.navigation', 'personal.elements', 'personal']
);
style(Application::APP_NAME, 'personal');

?>


<fieldset id="cmspicoPersonalSettings">

<div class="section">
	<h2><?php p($l->t('Site Folders (Pico CMS)')) ?></h2>

	<table cellpadding="10" cellpadding="5">
		<tr>
			<td colspan="2">

				Site folders allows you to create a website as a sub-folder of the cloud.<br/>
				Using the Pico CMS, your files - in Markdown format - will be parsed and served up
				to browsers as html.<br/>

			</td>
		</tr>
	</table>
</div>

<div class="section">
	<h2><?php p($l->t('Your current websites')); ?></h2>

	<table cellpadding="10" cellpadding="5">

		<tr>
			<td colspan="2">
				<table cellspacing="3" cellpadding="3" id="cms_pico_list_websites"
					   style="margin: 20px; width: 800px;">
					<tr class="header">
						<td width="25%">&nbsp;&nbsp;&nbsp;<?php p($l->t('Name')); ?></td>
						<td width="40%">&nbsp;&nbsp;&nbsp;<?php p($l->t('Address')); ?> / <?php p(
								$l->t('Local directory')
							); ?></td>
						<td width="20%">&nbsp;&nbsp;&nbsp;<?php p($l->t('Theme')); ?></td>
						<td width="15%"><?php p($l->t('Private')); ?></td>
					</tr>

				</table>
			</td>
		</tr>
	</table>
</div>

<div class="section">
	<h2><?php p($l->t('Create a new website')); ?></h2>

	<table cellpadding="10" cellpadding="5">
		<tr>
			<td colspan="2">
				To create a new Site Folder, you will need to specify the front URL address
				(<?php print(OC::$WEBROOT); ?>/sites/your_site_folder)
				that <br/>
				will be used to access your site and a local directory in your files where your templates
				will be stored.<br/>
			</td>
		</tr>

		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr class="lane">
			<td class="left"><?php p($l->t('Name of the website:')); ?><br/>
				<em><?php p($l->t('The title of your website')); ?></em></td>
			<td class="right">
				<input id="cms_pico_new_name" class="field250" value=""
					   placeholder="<?php p($l->t('My new site folder')); ?>"/>
			</td>
		</tr>

		<tr class="lane">
			<td class="left"><?php p($l->t('Address of the website:')); ?><br/>
				<em id="cms_pico_new_url"> </em></td>
			<td class="right">
				<input id="cms_pico_new_website" class="field250" value=""
					   placeholder="<?php p($l->t('my_site')); ?>"/>
			</td>
		</tr>

		<tr class="lane">
			<td class="left"><?php p($l->t('Local directory:')); ?><br/>
				<em><?php p($l->t('The place to store the website files on your cloud')); ?></em></td>
			<td class="right">
				<input type="submit" class="field250" id="cms_pico_new_folder"
					   value="/"/>
			</td>
		</tr>

		<tr class="lane">
			<td class="left"><?php p($l->t('Base Template:')); ?><br/>
				<em><?php p($l->t('Choose a template from the list')); ?></em></td>
			<td class="right">
				<select id="cms_pico_new_template" class="field250">
					<?php
					for ($i = 0; $i < sizeof($_['templates']); $i++) {
						echo '<option value="' . $i . '">' . $_['templates'][$i] . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr class="lane">
			<td colspan="2" class="center">
				<input class="field250" type="submit" id="cms_pico_new_submit"
					   value="<?php p($l->t('Create a new website')); ?>"/>
			</td>
		</tr>

	</table>

	<script id="tmpl_website" type="text/template">
		<tr class="entry" data-id="%%id%%" data-name="%%name%%" data-address="%%address%%"
			data-path="%%path%%" data-theme="%%theme%%" data-private="%%private%%">
			<td style="font-style: italic; font-weight: bold">%%name%%</td>
			<td>
				<table>
					<tr>
						<td class="link">%%address%%</td>
					</tr>
					<tr>
						<td class="path">%%path%%</td>
					</tr>
				</table>
			</td>
			<td><select class="theme"></select></select></td>
			<td>&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" class="private"/></td>
			<td>
				<button id="delete" class="icon-delete"
						title="<?php p($l->t('Delete website')); ?>"></button>
			</td>
		</tr>
	</script>

</div>

</fieldset>