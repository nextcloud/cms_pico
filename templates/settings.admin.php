<?php

use OCA\CMSPico\AppInfo\Application;

script(Application::APP_NAME, 'admin');
style(Application::APP_NAME, 'admin');

?>

<div class="section">
	<h2><?php p($l->t('Pico CMS')) ?></h2>

	<table cellpadding="10" cellpadding="5">
		<tr class="lane">
			<td colspan="2" class="left">Allow something:<br/>
				<em>This is an example of a checkbox.</em></td>
			<td class="right">
				<input type="checkbox" value="1" id="test_cms_pico"/>
			</td>
		</tr>
	</table>
</div>