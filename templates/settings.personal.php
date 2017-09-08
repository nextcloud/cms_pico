<?php


use OCA\CMSPico\AppInfo\Application;


script(Application::APP_NAME, 'vendor/notyf');
style(Application::APP_NAME, 'notyf');

script(Application::APP_NAME, 'personal');
style(Application::APP_NAME, 'personal');

?>

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


		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" class="title">Your current websites</td>
		</tr>
		<tr>
			<td colspan="2">
				<table cellspacing="3" cellpadding="3" id="cms_pico_list_websites" style="margin: 20px; width: 700px;">
					<tr class="header">
						<td width="33%">Name</td>
						<td width="33%">Address</td>
						<td width="33%">Local directory</td>
					</tr>

				</table>
			</td>
		</tr>


		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" class="title">Create a new website</td>
		</tr>
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
			<td class="left">Name of the website:<br/>
				<em>The title of your website</em></td>
			<td class="right">
				<input id="cms_pico_new_name" class="field250" value=""
					   placeholder="My new site folder"/>
			</td>
		</tr>

		<tr class="lane">
			<td class="left">Address of the website:<br/>
				<em id="cms_pico_new_url"> </em></td>
			<td class="right">
				<input id="cms_pico_new_website" class="field250" value="" placeholder="my_site"/>
			</td>
		</tr>

		<tr class="lane">
			<td class="left">Local directory:<br/>
				<em><?php p($l->t('The place to store the website files on your cloud')); ?></em></td>
			<td class="right">
				<div style="display: inline;">
					<div style="display: inline;" id="cms_pico_new_path">/</div>
					<div style="margin-left: 50px; display: inline;">
						<input type="submit" class="field250" id="cms_pico_new_folder"
							   value="Choose a folder"/>
					</div>
				</div>
			</td>
		</tr>

		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr class="lane">
			<td colspan="2" class="center">
				<input class="field250" type="submit" id="cms_pico_new_submit"
					   value="Create a new website"/>
			</td>
		</tr>

	</table>

	<script id="tmpl_website" type="text/template">
		<tr class="entry" data-id="%id%" data-address="%%address%%" data-path="%%path%%">
			<td style="font-style: italic; font-weight: bold">%%name%%</td>
			<td class="link">%%address%%</td>
			<td class="path">%%path%%</td>
		</tr>
	</script>

</div>

