<?php

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