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
	<h2><?php p($l->t('Not found')); ?></h2>
	<p class="infogroup"><?php if(isset($_['message'])) p($_['message'])?></p>
	<p><a class="button primary" href="<?php p(\OC::$server->getURLGenerator()->linkTo('', 'index.php')) ?>">
		<?php p($l->t('Back to %s', array($theme->getName()))); ?>
	</a></p>
</div>
