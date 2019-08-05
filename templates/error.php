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

/** @var $_ array */
/** @var $l \OCP\IL10N */
/** @var $theme OCP\Defaults */

?>

<div class="body-login-container update">
	<div class="icon-big icon-error icon-white"></div>
	<h2><?php p($l->t('Internal Server Error')); ?></h2>
	<p class="infogroup"><?php if (isset($_['message'])) { p($_['message']); } else { p($l->t('The server was unable to complete your request.')); } ?></p>
	<p class="infogroup"><?php p($l->t('If this happens again, please send the technical details below to the server administrator.')) ?></p>

	<ul class="infogroup">
		<li><?php p($l->t('Remote Address: %s', [$_['remoteAddr']])) ?></li>
		<li><?php p($l->t('Request ID: %s', [$_['requestID']])) ?></li>
	</ul>

	<p class="infogroup"><?php p($l->t('More details can be found in the server log.')) ?></p>

	<p><a class="button primary" href="<?php p(\OC::$server->getURLGenerator()->linkTo('', 'index.php')) ?>">
		<?php p($l->t('Back to %s', [ $theme->getName() ])); ?>
	</a></p>
</div>

<?php if ($_['debugMode'] && $_['errorClass']) { ?>
	<div class="error error-wide">
		<h2><?php p($l->t('Technical details')) ?></h2>
		<ul>
			<li><?php p($l->t('Remote Address: %s', [ $_['remoteAddr'] ])) ?></li>
			<li><?php p($l->t('Request ID: %s', [ $_['requestID'] ])) ?></li>
			<li><?php p($l->t('Type: %s', [ $_['errorClass'] ])) ?></li>
			<li><?php p($l->t('Code: %s', [ $_['errorCode'] ])) ?></li>
			<li><?php p($l->t('Message: %s', [ $_['errorMsg'] ])) ?></li>
			<li><?php p($l->t('File: %s', [ $_['errorFile'] ])) ?></li>
			<li><?php p($l->t('Line: %s', [ $_['errorLine'] ])) ?></li>
		</ul>

		<br />
		<h2><?php p($l->t('Trace')) ?></h2>
		<pre><?php p($_['errorTrace']) ?></pre>
	</div>
<?php } ?>
