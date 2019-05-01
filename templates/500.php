<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
/** @var $theme OCP\Defaults */
// @codeCoverageIgnoreStart
if(!isset($_)) {//standalone  page is not supported anymore - redirect to /
	require_once '../../lib/base.php';

	$urlGenerator = \OC::$server->getURLGenerator();
	header('Location: ' . $urlGenerator->getAbsoluteURL('/'));
	exit;
}
// @codeCoverageIgnoreEnd
?>
<div class="body-login-container update">
	<div class="icon-big icon-error icon-white"></div>
	<h2><?php p($l->t('Internal Server Error')); ?></h2>
	<p class="infogroup"><?php if(isset($_['message'])) { p($_['message']); } else { p($l->t('The server was unable to complete your request.')); } ?></p>
	<p class="infogroup"><?php p($l->t('If this happens again, please send the technical details below to the server administrator.')) ?></p>

	<ul class="infogroup">
		<li><?php p($l->t('Remote Address: %s', [$_['remoteAddr']])) ?></li>
		<li><?php p($l->t('Request ID: %s', [$_['requestID']])) ?></li>
	</ul>

	<p class="infogroup"><?php p($l->t('More details can be found in the server log.')) ?></p>

	<p><a class="button primary" href="<?php p(\OC::$server->getURLGenerator()->linkTo('', 'index.php')) ?>">
		<?php p($l->t('Back to %s', array($theme->getName()))); ?>
	</a></p>
</div>

<?php if($_['debugMode']): ?>
	<div class="error error-wide">
		<h2><?php p($l->t('Technical details')) ?></h2>
		<ul>
			<li><?php p($l->t('Remote Address: %s', [$_['remoteAddr']])) ?></li>
			<li><?php p($l->t('Request ID: %s', [$_['requestID']])) ?></li>
			<li><?php p($l->t('Type: %s', [$_['errorClass']])) ?></li>
			<li><?php p($l->t('Code: %s', [$_['errorCode']])) ?></li>
			<li><?php p($l->t('Message: %s', [$_['errorMsg']])) ?></li>
			<li><?php p($l->t('File: %s', [$_['errorFile']])) ?></li>
			<li><?php p($l->t('Line: %s', [$_['errorLine']])) ?></li>
		</ul>

		<br />
		<h2><?php p($l->t('Trace')) ?></h2>
		<pre><?php p($_['errorTrace']) ?></pre>
	</div>
<?php endif; ?>
