<?php


use OCA\CMSPico\AppInfo\Application;

script(Application::APP_NAME, 'personal');
style(Application::APP_NAME, 'personal');

?>

<div class="section">
	<h2><?php p($l->t('Pico CMS')) ?></h2>

	<table cellpadding="10" cellpadding="5" style="width: 200px;">
		<tr style="width: 200px;">
			<td style="width: 200px;">

				Site folders must contain files in markdown format - with extension 'md'.
				These files will be parsed and served up to browsers as html by Pico CMS.
				Click on the plus below to add a folder. You must youself create a "content"
				subfolder inside this folder and populate it with pages in Markdown format -
				plus a "themes" subfolder if you want to customize your pages.
				A site will be served at the URL <?php print(OC::$WEBROOT); ?>/sites/site_folder, where
				"site_folder" is the name of site folder you choose.
				<br/><br/>
				Or use the website wizard to create a simple site.
				<br/><br/>

			</td>
		</tr>
	</table>

	<table cellpadding="10" cellpadding="5" id="cms_pico_list_websites">
		<tr><td colspan="2" class="title">Your current websites</td></tr>
	</table>

	<table cellpadding="10" cellpadding="5">

		<tr>
			<td colspan="2" class="title">Create a new website</td>
		</tr>

		<tr class="lane">
			<td class="left">Name of the website:<br/>
				<em id="cms_pico_new_url">http://nextcloud/sites/example</em></td>
			<td class="right">
				<input id="cms_pico_new_website" class="field250" value=""/>
			</td>
		</tr>

		<tr class="lane">
			<td class="left">Local directory:<br/>
				<em><?php p($l->t('The place to store the website files on your cloud')); ?></em></td>
			<td class="right">
				<div style="display: inline;">
					<div style="display: inline;" id="cms_pico_new_path">/</div>
					<div style="margin-left: 50px; display: inline;">
						<input type="submit" class="field250" id="cms_pico_new_folder" value="Pick a folder"/>
					</div>
				</div>
			</td>
		</tr>

		<tr><td>&nbsp;</td></tr>
		<tr class="lane">
			<td colspan="2" class="center">
				<input class="field250" type="submit" id="cms_pico_new_submit" value="Create a new website" />
			</td>
		</tr>

	</table>
</div>

