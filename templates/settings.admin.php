<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2017, Maxence Lange (<maxence@artificial-owl.com>)
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

use OCA\CMSPico\AppInfo\Application;

/** @var $_ array */
/** @var $l \OCP\IL10N */
script(Application::APP_NAME, [ 'pico', 'admin' ]);
style(Application::APP_NAME, 'pico');

?>

<?php if (!$_['parsedownCompatible']) { ?>
	<article class="section">
		<div class="message large error">
			<div class="icon icon-error-color"></div>
			<div>
				<p><strong><?php p($l->t(
					'Your Nextcloud installation is incompatible with Pico CMS for Nextcloud!'
				)); ?></strong></p>
				<p><?php p($l->t(
					'Some of your Nextcloud apps have known incompatibilities with Pico CMS for Nextcloud. This is '
							. 'no-one\'s fault, neither are Nextcloud nor the conflicting apps to blame, this is just '
							. 'some technical limitation of Nextcloud\'s app infrastructure we can\'t solve in the '
							. 'short term. We\'re working on a solution! In the meantime you must remove the '
							. 'conflicting apps. Known conflicting apps are "Issue Template" and "Terms of service".'
				 )); ?></p>
				<p><?php print_unescaped($l->t(
					'If you see the error <code>"Call to undefined method ParsedownExtra::textElements()"</code> '
							. 'in Nextcloud\'s logs even though you\'ve removed all conflicting apps, please don\'t '
							. 'hesitate to <a href="https://github.com/nextcloud/cms_pico/issues/new">open a new Issue '
							. 'on GitHub</a> with a copy of the error including its stack trace and a complete list '
							. 'of all apps installed.'
				)); ?></p>
			</div>
		</div>
	</article>
<?php } ?>

<article class="section">
	<h2><?php p($l->t('Pico CMS for Nextcloud')); ?></h2>
	<p class="settings-hint"><?php p($l->t(
		'Change Pico CMS for Nextcloud\'s behavior and manage optional features.'
	)); ?></p>

	<section class="lane">
		<header>
			<h3 class="select2-align"><?php p($l->t('Limit to groups')); ?></h3>
		</header>
		<section>
			<form id="picocms-limit_groups" class="picocms-limit_groups-form"
					data-route="/apps/cms_pico/admin/limit_groups">
				<div>
					<input type="hidden" class="input select2-placeholder" name="data[limit_groups]"
							value="<?php p(implode('|', $_['limitGroups'])); ?>" />
					<div class="message input select2-loading">
						<div class="icon icon-loading"></div>
						<div>
							<p><?php p($l->t('Loading groups…')); ?></p>
						</div>
					</div>
				</div>
				<p class="note"><?php p($l->t(
					'If you wish not to enable all of your users to create personal websites, you can limit Pico CMS '
							. 'for Nextcloud to certain groups. Select the groups you want to limit access to. If you '
							. 'leave this field empty, usage isn\'t limited. Revoking access for certain groups won\'t '
							. 'delete any of a user\'s websites, however, they get inaccessible.'
				)); ?></p>
			</form>
		</section>
	</section>
</article>

<article class="section">
	<h2><?php p($l->t('Custom themes')); ?></h2>
	<p class="settings-hint"><?php p($l->t(
		'Add custom themes for greater individuality and style.'
	)); ?></p>

	<div class="message large">
		<div class="icon icon-info"></div>
		<div>
			<p><?php p($l->t(
				'Pico CMS for Nextcloud allows you to add custom themes for some greater individuality and style. '
						. 'However, for security reasons, users can\'t add custom themes on their own. Before you can '
						. 'add a new custom theme using the "Add custom theme" button below, you\'ll have to upload '
						. 'all of its files to the data folder of your Nextcloud instance. After uploading the theme '
						. 'it will show up in the form below to actually allow users to use the custom theme. If you '
						. 'want to modify one of your previously added custom themes, simply edit the corresponding '
						. 'files in Nextcloud\'s data folder. For the changes to take effect you must hit the "Reload '
						. 'custom theme" button next to the edited theme in the form below.'
			)); ?></p>
			<p><?php p($l->t(
				'Before adding a new custom theme, upload all of the theme\'s files to a new folder in the following '
						. 'directory. If you want to edit one of your custom themes, refer to this directory likewise.'
			)); ?>
			<p class="followup indent"><code><?php p($_['themesPath']); ?></code></p>
		</div>
	</div>

	<section id="picocms-themes" class="picocms-admin-list"
			data-route="/apps/cms_pico/admin/themes"
			data-template="#picocms-themes-template"
			data-system-template="#picocms-themes-template-system-item"
			data-custom-template="#picocms-themes-template-custom-item"
			data-new-template="#picocms-themes-template-new-item"
			data-copy-template="#picocms-themes-template-copy-item"
			data-loading-template="#picocms-themes-template-loading"
			data-error-template="#picocms-themes-template-error">
		<div class="app-content-loading message large">
			<div class="icon loading"></div>
			<div>
				<p><?php p($l->t('Loading themes…')); ?></p>
			</div>
		</div>
	</section>

	<script id="picocms-themes-template" type="text/template"
			data-replaces="#picocms-themes">
		<div class="app-content-list">
			<div class="app-content-list-item app-content-list-add">
				<div class="app-content-list-item-line-one">
					<select class="action-new-item"></select>
					<button class="action-new has-tooltip" title="<?php p($l->t('Add custom theme')); ?>">
						<span class="icon icon-add"></span>
						<span class="hidden-visually"><?php p($l->t('Add custom theme')); ?></span>
					</button>
				</div>
				<div class="action-reload icon-redo-alt has-tooltip" data-placement="left"
						title="<?php p($l->t('Reload themes list')); ?>">
					<span class="hidden-visually"><?php p($l->t('Reload themes list')); ?></span>
				</div>
			</div>
		</div>
	</script>

	<script id="picocms-themes-template-system-item" type="text/template"
			data-append-to="#picocms-themes > .app-content-list">
		<div class="app-content-list-item">
			<div class="app-content-list-item-line-one">
				<p>{name}</p>
				<div class="info-compat message">
					<div class="icon-checkmark has-tooltip" title="<?php p($l->t('Compatible theme.')); ?>"></div>
					<div>
						<p class="note"><?php p($l->t('System theme')); ?></p>
					</div>
				</div>
			</div>
			<div class="action-copy icon-copy has-tooltip" data-placement="left"
					title="<?php p($l->t('Copy system theme')); ?>">
				<span class="hidden-visually"><?php p($l->t('Copy system theme')); ?></span>
			</div>
		</div>
	</script>

	<script id="picocms-themes-template-custom-item" type="text/template"
			data-append-to="#picocms-themes > .app-content-list">
		<div class="app-content-list-item">
			<div class="app-content-list-item-line-one">
				<p>{name}</p>
				<div class="info-compat message">
					<div class="icon-checkmark has-tooltip" title="<?php p($l->t('Compatible theme.')); ?>"></div>
					<div>
						<p class="note"><?php p($l->t('Custom theme')); ?></p>
					</div>
				</div>
			</div>
			<div class="action-sync icon-sync-alt has-tooltip" data-placement="left"
					title="<?php p($l->t('Reload custom theme')); ?>">
				<span class="hidden-visually"><?php p($l->t('Reload custom theme')); ?></span>
			</div>
			<div class="action-copy icon-copy has-tooltip" data-placement="left"
					title="<?php p($l->t('Copy custom theme')); ?>">
				<span class="hidden-visually"><?php p($l->t('Copy custom theme')); ?></span>
			</div>
			<div class="action-delete icon-delete has-tooltip" data-placement="left"
					title="<?php p($l->t('Delete custom theme')); ?>">
				<span class="hidden-visually"><?php p($l->t('Delete custom theme')); ?></span>
			</div>
		</div>
	</script>

	<script id="picocms-themes-template-new-item" type="text/template"
			data-append-to="#picocms-themes > .app-content-list > .app-content-list-add select">
		<option name="{name}">{name}</option>
	</script>

	<script id="picocms-themes-template-copy-item" type="text/template">
		<form id="{id}" title="{title}" class="form">
			<fieldset>
				<div class="label">
					<label for="picocms-themes-copy-base"><?php p($l->t('Base theme')); ?></label>
				</div>
				<div class="content">
					<span id="picocms-themes-copy-base" class="input">{source}</span>
				</div>
			</fieldset>
			<fieldset>
				<div class="label">
					<label for="picocms-themes-copy-name"><?php p($l->t('Theme name')); ?></label>
				</div>
				<div class="content">
					<input id="picocms-themes-copy-name" class="input input-name" type="text" name="name"
							value="" placeholder="{source}" />
				</div>
			</fieldset>
		</form>
	</script>

	<script id="picocms-themes-template-loading" type="text/template"
			data-replaces="#picocms-themes">
		<div class="app-content-loading message large">
			<div class="icon loading"></div>
			<div>
				<p><?php p($l->t('Loading themes…')); ?></p>
			</div>
		</div>
	</script>

	<script id="picocms-themes-template-error" type="text/template"
			data-replaces="#picocms-themes">
		<div class="app-content-error message large">
			<div class="icon icon-error-color"></div>
			<div>
				<p><?php p($l->t(
					'A unexpected error occurred while performing this action. Please check Nextcloud\'s logs.'
				)); ?></p>
				<p class="error-details" style="display: none">
					<?php p($l->t('Error: {error}')); ?>
				</p>
				<p class="exception-details" style="display: none">
					<?php p($l->t('Encountered unexpected {exception}: {exceptionMessage}')); ?>
				</p>
			</div>
			<div class="action action-reload icon-redo-alt has-tooltip" data-placement="left"
					title="<?php p($l->t('Reload themes list')); ?>">
				<span class="hidden-visually"><?php p($l->t('Reload themes list')); ?></span>
			</div>
		</div>
	</script>
</article>

<article class="section">
	<h2><?php p($l->t('Custom plugins')); ?></h2>
	<p class="settings-hint"><?php p($l->t(
		'Add custom plugins to reach for Pico\'s full potential.'
	)); ?></p>

	<div class="message large">
		<div class="icon icon-info"></div>
		<div>
			<p><?php p($l->t(
				'Pico CMS for Nextcloud allows you to add custom plugins to really utilize all of Pico\'s power. '
						. 'Plugins work on a global basis, i.e. adding a custom plugin will enable it for all of your '
						. 'users\' websites. Before adding a new custom plugin using the "Add custom plugin" button '
						. 'below, you must upload all of the plugin\'s files to the data folder of your Nextcloud '
						. 'instance. After uploading the plugin it will show up in the form below to actually enable '
						. 'it. If you want to update one of your previously added custom plugins, simply replace the '
						. 'plugin\'s files in Nextcloud\'s data folder. For the changes to take effect you must hit '
						. 'the "Reload custom plugin" button next to the updated plugin in the form below.'
			)); ?></p>
			<p><?php p($l->t(
				'Before adding a new custom plugin, upload all of the plugin\'s files to a new folder in the following '
						. 'directory. If you want to update one of your custom plugins, refer to this directory '
						. 'likewise. Please note that the name of a plugin\'s folder must strictly match the name of '
						. 'the plugin, otherwise Pico will refuse to enable the plugin.'
			)); ?>
			<p class="followup indent"><code><?php p($_['pluginsPath']); ?></code></p>
		</div>
	</div>

	<section id="picocms-plugins" class="picocms-admin-list"
			data-route="/apps/cms_pico/admin/plugins"
			data-template="#picocms-plugins-template"
			data-system-template="#picocms-plugins-template-system-item"
			data-custom-template="#picocms-plugins-template-custom-item"
			data-new-template="#picocms-plugins-template-new-item"
			data-loading-template="#picocms-plugins-template-loading"
			data-error-template="#picocms-plugins-template-error">
		<div class="app-content-loading message large">
			<div class="icon loading"></div>
			<div>
				<p><?php p($l->t('Loading plugins…')); ?></p>
			</div>
		</div>
	</section>

	<script id="picocms-plugins-template" type="text/template"
			data-replaces="#picocms-plugins">
		<div class="app-content-list">
			<div class="app-content-list-item app-content-list-add">
				<div class="app-content-list-item-line-one">
					<select class="action-new-item"></select>
					<button class="action-new has-tooltip" title="<?php p($l->t('Add custom plugin')); ?>">
						<span class="icon icon-add"></span>
						<span class="hidden-visually"><?php p($l->t('Add custom plugin')); ?></span>
					</button>
				</div>
				<div class="action-reload icon-redo-alt has-tooltip" data-placement="left"
						title="<?php p($l->t('Reload plugins list')); ?>">
					<span class="hidden-visually"><?php p($l->t('Reload plugins list')); ?></span>
				</div>
			</div>
		</div>
	</script>

	<script id="picocms-plugins-template-system-item" type="text/template"
			data-append-to="#picocms-plugins > .app-content-list">
		<div class="app-content-list-item">
			<div class="app-content-list-item-line-one">
				<p>{name}</p>
				<div class="info-compat message">
					<div class="icon-checkmark has-tooltip" title="<?php p($l->t('Compatible plugin.')); ?>"></div>
					<div>
						<p class="note"><?php p($l->t('System plugin')); ?></p>
					</div>
				</div>
			</div>
		</div>
	</script>

	<script id="picocms-plugins-template-custom-item" type="text/template"
			data-append-to="#picocms-plugins > .app-content-list">
		<div class="app-content-list-item">
			<div class="app-content-list-item-line-one">
				<p>{name}</p>
				<div class="info-compat message">
					<div class="icon-checkmark has-tooltip" title="<?php p($l->t('Compatible plugin.')); ?>"></div>
					<div>
						<p class="note"><?php p($l->t('Custom plugin')); ?></p>
					</div>
				</div>
			</div>
			<div class="action-sync icon-sync-alt has-tooltip" data-placement="left"
					title="<?php p($l->t('Reload custom plugin')); ?>">
				<span class="hidden-visually"><?php p($l->t('Reload custom plugin')); ?></span>
			</div>
			<div class="action-delete icon-delete has-tooltip" data-placement="left"
					title="<?php p($l->t('Delete custom plugin')); ?>">
				<span class="hidden-visually"><?php p($l->t('Delete custom plugin')); ?></span>
			</div>
		</div>
	</script>

	<script id="picocms-plugins-template-new-item" type="text/template"
			data-append-to="#picocms-plugins > .app-content-list > .app-content-list-add select">
		<option name="{name}">{name}</option>
	</script>

	<script id="picocms-plugins-template-loading" type="text/template"
			data-replaces="#picocms-plugins">
		<div class="app-content-loading message large">
			<div class="icon loading"></div>
			<div>
				<p><?php p($l->t('Loading plugins…')); ?></p>
			</div>
		</div>
	</script>

	<script id="picocms-plugins-template-error" type="text/template"
			data-replaces="#picocms-plugins">
		<div class="app-content-error message large">
			<div class="icon icon-error-color"></div>
			<div>
				<p><?php p($l->t(
					'A unexpected error occurred while performing this action. Please check Nextcloud\'s logs.'
				)); ?></p>
				<p class="error-details" style="display: none">
					<?php p($l->t('Error: {error}')); ?>
				</p>
				<p class="exception-details" style="display: none">
					<?php p($l->t('Encountered unexpected {exception}: {exceptionMessage}')); ?>
				</p>
			</div>
			<div class="action action-reload icon-redo-alt has-tooltip" data-placement="left"
					title="<?php p($l->t('Reload plugins list')); ?>">
				<span class="hidden-visually"><?php p($l->t('Reload plugins list')); ?></span>
			</div>
		</div>
	</script>
</article>

<article class="section">
	<h2><?php p($l->t('Custom templates')); ?></h2>
	<p class="settings-hint"><?php p($l->t(
		'Make it easier for users to create new websites.'
	)); ?></p>

	<div class="message large">
		<div class="icon icon-info"></div>
		<div>
			<p><?php p($l->t(
				'Creating new websites can be hard - where to even start? Custom templates act as a starting point for '
						. 'users to create a new website using Pico CMS for Nextcloud. Before adding a new custom '
						. 'template using the "Add custom template" button below, you must upload all of the '
						. 'template\'s files to the data folder of your Nextcloud instance. After uploading the '
						. 'template it will show up in the form below to actually add it to the "Create a new website" '
						. 'form of your users. If you want to modify one of your previously added custom templates, '
						. 'simply edit the corresponding files in Nextcloud\'s data folder.'
			)); ?></p>
			<p><?php p($l->t(
				'Before adding a new custom template, upload all of the template\'s files to a new folder in the '
						. 'following directory:'
			)); ?>
			<p class="followup indent"><code><?php p($_['templatesPath']); ?></code></p>
		</div>
	</div>

	<section id="picocms-templates" class="picocms-admin-list"
			data-route="/apps/cms_pico/admin/templates"
			data-template="#picocms-templates-template"
			data-system-template="#picocms-templates-template-system-item"
			data-custom-template="#picocms-templates-template-custom-item"
			data-new-template="#picocms-templates-template-new-item"
			data-copy-template="#picocms-templates-template-copy-item"
			data-loading-template="#picocms-templates-template-loading"
			data-error-template="#picocms-templates-template-error">
		<div class="app-content-loading message large">
			<div class="icon loading"></div>
			<div>
				<p><?php p($l->t('Loading templates…')); ?></p>
			</div>
		</div>
	</section>

	<script id="picocms-templates-template" type="text/template"
			data-replaces="#picocms-templates">
		<div class="app-content-list">
			<div class="app-content-list-item app-content-list-add">
				<div class="app-content-list-item-line-one">
					<select class="action-new-item"></select>
					<button class="action-new has-tooltip" title="<?php p($l->t('Add custom template')); ?>">
						<span class="icon icon-add"></span>
						<span class="hidden-visually"><?php p($l->t('Add custom template')); ?></span>
					</button>
				</div>
				<div class="action-reload icon-redo-alt has-tooltip" data-placement="left"
						title="<?php p($l->t('Reload templates list')); ?>">
					<span class="hidden-visually"><?php p($l->t('Reload templates list')); ?></span>
				</div>
			</div>
		</div>
	</script>

	<script id="picocms-templates-template-system-item" type="text/template"
			data-append-to="#picocms-templates > .app-content-list">
		<div class="app-content-list-item">
			<div class="app-content-list-item-line-one">
				<p>{name}</p>
				<div class="info-compat message">
					<div class="icon-checkmark has-tooltip" title="<?php p($l->t('Compatible template.')); ?>"></div>
					<div>
						<p class="note"><?php p($l->t('System template')); ?></p>
					</div>
				</div>
			</div>
			<div class="action-copy icon-copy has-tooltip" data-placement="left"
					title="<?php p($l->t('Copy system template')); ?>">
				<span class="hidden-visually"><?php p($l->t('Copy system template')); ?></span>
			</div>
		</div>
	</script>

	<script id="picocms-templates-template-custom-item" type="text/template"
			data-append-to="#picocms-templates > .app-content-list">
		<div class="app-content-list-item">
			<div class="app-content-list-item-line-one">
				<p>{name}</p>
				<div class="info-compat message">
					<div class="icon-checkmark has-tooltip" title="<?php p($l->t('Compatible template.')); ?>"></div>
					<div>
						<p class="note"><?php p($l->t('Custom template')); ?></p>
					</div>
				</div>
			</div>
			<div class="action-copy icon-copy has-tooltip" data-placement="left"
					title="<?php p($l->t('Copy custom template')); ?>">
				<span class="hidden-visually"><?php p($l->t('Copy custom template')); ?></span>
			</div>
			<div class="action-delete icon-delete has-tooltip" data-placement="left"
					title="<?php p($l->t('Delete custom template')); ?>">
				<span class="hidden-visually"><?php p($l->t('Delete custom template')); ?></span>
			</div>
		</div>
	</script>

	<script id="picocms-templates-template-new-item" type="text/template"
			data-append-to="#picocms-templates > .app-content-list > .app-content-list-add select">
		<option name="{name}">{name}</option>
	</script>

	<script id="picocms-templates-template-copy-item" type="text/template">
		<form id="{id}" title="{title}" class="form">
			<fieldset>
				<div class="label">
					<label for="picocms-templates-copy-base"><?php p($l->t('Base template')); ?></label>
				</div>
				<div class="content">
					<span id="picocms-templates-copy-base" class="input">{source}</span>
				</div>
			</fieldset>
			<fieldset>
				<div class="label">
					<label for="picocms-templates-copy-name"><?php p($l->t('Template name')); ?></label>
				</div>
				<div class="content">
					<input id="picocms-templates-copy-name" class="input input-name" type="text" name="name"
							value="" placeholder="{source}" />
				</div>
			</fieldset>
		</form>
	</script>

	<script id="picocms-templates-template-loading" type="text/template"
			data-replaces="#picocms-templates">
		<div class="app-content-loading message large">
			<div class="icon loading"></div>
			<div>
				<p><?php p($l->t('Loading templates…')); ?></p>
			</div>
		</div>
	</script>

	<script id="picocms-templates-template-error" type="text/template"
			data-replaces="#picocms-templates">
		<div class="app-content-error message large">
			<div class="icon icon-error-color"></div>
			<div>
				<p><?php p($l->t(
					'A unexpected error occurred while performing this action. Please check Nextcloud\'s logs.'
				)); ?></p>
				<p class="error-details" style="display: none">
					<?php p($l->t('Error: {error}')); ?>
				</p>
				<p class="exception-details" style="display: none">
					<?php p($l->t('Encountered unexpected {exception}: {exceptionMessage}')); ?>
				</p>
			</div>
			<div class="action action-reload icon-redo-alt has-tooltip" data-placement="left"
					title="<?php p($l->t('Reload templates list')); ?>">
				<span class="hidden-visually"><?php p($l->t('Reload templates list')); ?></span>
			</div>
		</div>
	</script>
</article>

<article class="section">
	<h2><?php p($l->t('Configure your webserver')); ?></h2>
	<p class="settings-hint"><?php p($l->t(
		'Enable Pico CMS for Nextcloud\'s full potential by configuring your webserver appropriately.'
	)); ?></p>

	<div class="message large">
		<div class="icon icon-info"></div>
		<div>
			<p><?php p($l->t(
				'Depending on your webserver\'s configuration, users can access their websites using different URLs. '
						. 'By default, users can access their websites using Pico CMS for Nextcloud\'s full '
						. 'application URL. However, these URLs are pretty long and thus not very user-friendly. For '
						. 'this reason, Pico CMS for Nextcloud also supports shortened URLs utilizing the virtual '
						. '"sites/" folder. However, using this feature requires some additional webserver '
						. 'configuration. If you\'re using the Apache webserver, try one of the first two examples '
						. 'shown below. If you\'re rather using the nginx webserver, try one of last two examples. If '
						. 'you don\'t really understand what\'s going on, contact your server administrator and send '
						. 'him the information below. If your server administrator tells you this isn\'t possible, '
						. 'don\'t despair - you can still use Pico CMS for Nextcloud\'s full application URLs, they '
						. 'always work out-of-the-box.'
			)); ?></p>
		</div>
	</div>

	<section class="lane">
		<?php $internalPathRegex = '^' . preg_quote($_['internalPath']) . '(.*)$'; ?>
		<?php $internalPathReplacement = $_['internalFullUrl'] . '$1'; ?>

		<header>
			<h3><?php p($l->t('Enable short website URLs')); ?></h3>
		</header>
		<section>
			<form id="picocms-link_mode" class="picocms-link_mode-form"
					data-route="/apps/cms_pico/admin/link_mode">
				<p>
					<input type="radio" id="picocms-link_mode_long" class="radio"
							name="data[link_mode]" value="<?php p($_['linkModeLong']); ?>"
							<?php if ($_['linkMode'] === $_['linkModeLong']) { ?>checked="checked"<?php } ?>>
					<label for="picocms-link_mode_long">
						<?php p($l->t('Full application URLs')); ?>
						<span class="note">– <a><?php p($_['exampleFullUrl']); ?></a></span>
					</label>
				</p>
				<p>
					<input type="radio" id="picocms-link_mode_short" class="radio"
							name="data[link_mode]" value="<?php p($_['linkModeShort']); ?>"
							<?php if ($_['linkMode'] === $_['linkModeShort']) { ?>checked="checked"<?php } ?>>
					<label for="picocms-link_mode_short">
						<?php p($l->t('Short website URLs')); ?>
						<span class="note">– <a><?php p($_['exampleProxyUrl']); ?></a></span>
					</label>
				</p>
				<p class="note"><?php p($l->t(
					'After you\'ve configured your webserver to enable shortened URLs, you should select the '
							. 'corresponding option above to let your users know about this feature. Don\'t enable '
							. 'this option if you haven\'t configured the virtual "sites/" folder yet using one of the '
							. 'configuration examples shown below.'
				)); ?></p>
			</form>
		</section>
	</section>

	<section class="lane">
		<header>
			<h3><?php p($l->t('Using Apache\'s mod_proxy')); ?></h3>
			<p>
				<?php p($l->t('Your users\' website URLs will look like the following:')); ?>
				<a><?php p($_['exampleProxyUrl']); ?></a>
			</p>
		</header>
		<section>
			<p class="code">
				<code>
					ProxyPass <?php p($_['internalPath']); ?> <?php p($_['internalProxyUrl']); ?><br/>
					ProxyPassReverse <?php p($_['internalPath']); ?> <?php p($_['internalProxyUrl']); ?><br/>
					<?php if (substr_compare($_['internalProxyUrl'], 'https', 0, 5) === 0) { ?>
						SSLProxyEngine on<br/>
					<?php } ?>
				</code>
			</p>
			<p><?php p($l->t(
				'Copy the config snippet above to Nextcloud\'s <VirtualHost …> section of your apache.conf. Before '
						. 'doing so you must enable both Apache\'s mod_proxy and mod_proxy_http modules. Otherwise '
						. 'your webserver will either refuse to (re)start or yield a 500 Internal Server Error.'
			)); ?></p>
		</section>
	</section>

	<section class="lane">
		<header>
			<h3><?php p($l->t('Using Apache\'s mod_rewrite')); ?></h3>
			<p>
				<?php p($l->t('Your users\' website URLs will look like the following:')); ?>
				<a><?php p($_['exampleFullUrl']); ?></a>
			</p>
		</header>
		<section>
			<p class="code">
				<code>
					RewriteEngine On<br/>
					RewriteRule <?php p($internalPathRegex); ?> <?php p($internalPathReplacement); ?> [QSA,L]<br/>
				</code>
			</p>
			<p><?php p($l->t(
				'Before copying the config snippet above to Nextcloud\'s <VirtualHost …> section of your apache.conf, '
						. 'make sure to enable Apache\'s mod_rewrite module. Otherwise your webserver will refuse to '
						. '(re)start or yield a 500 Internal Server Error. Please note that this config won\'t '
						. 'actually let you use shortened URLs, it just redirects users from shortened URLs to the '
						. 'site\'s full URL. Thus you should prefer the solution utilizing mod_proxy shown above.'
			)); ?></p>
		</section>
	</section>

	<section class="lane">
		<header>
			<h3><?php p($l->t('Using nginx\'s proxy_pass')); ?></h3>
			<p>
				<?php p($l->t('Your users\' website URLs will look like the following:')); ?>
				<a><?php p($_['exampleProxyUrl']); ?></a>
			</p>
		</header>
		<section>
			<p class="code">
				<code>
					location ^~ <?php p($_['internalPath']); ?> {<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;proxy_set_header X-Forwarded-Host $host:$server_port;<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;proxy_set_header X-Forwarded-Server $host;<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;proxy_pass <?php p($_['internalProxyUrl']); ?>;<br/>
					<?php if (substr_compare($_['internalProxyUrl'], 'https', 0, 5) === 0) { ?>
						&nbsp;&nbsp;&nbsp;&nbsp;proxy_ssl_server_name on;<br/>
					<?php } ?>
					}<br/>
				</code>
			</p>
			<p><?php p($l->t(
				'Copy the config snippet above to Nextcloud\'s server { … } section of your nginx.conf. Before doing '
						. 'doing so you must enable nginx\'s ngx_http_proxy_module module. Otherwise your webserver '
						. 'will either refuse to (re)start or yield a 500 Internal Server Error.'
			)); ?></p>
		</section>
	</section>

	<section class="lane">
		<header>
			<h3><?php p($l->t('Using nginx\'s rewrite')); ?></h3>
			<p>
				<?php p($l->t('Your users\' website URLs will look like the following:')); ?>
				<a><?php p($_['exampleFullUrl']); ?></a>
			</p>
		</header>
		<section>
			<p class="code">
				<code>
					rewrite <?php p($internalPathRegex); ?> <?php p($internalPathReplacement); ?> last;<br/>
				</code>
			</p>
			<p><?php p($l->t(
				'Simply copy the config snippet above to Nextcloud\'s server { … } section of your nginx.conf. Please '
						. 'note that this config won\'t actually let you use shortened URLs, it just redirects users '
						. 'from shortened URLs to the site\'s full URL. Thus you should prefer the solution utilizing '
						. 'nginx\'s proxy_pass directive shown above.'
			)); ?></p>
		</section>
	</section>
</article>

<article class="section">
	<h2><?php p($l->t('Version information')); ?></h2>

	<p>
		<strong>Pico CMS for Nextcloud <?php p($_['appVersion']); ?></strong>
		– <a href="https://apps.nextcloud.com/apps/cms_pico">https://apps.nextcloud.com/apps/cms_pico</a><br>
		<?php print_unescaped($l->t(
			'Pico CMS for Nextcloud was made by <a href="https://github.com/daita">Maxence Lange</a> and '
					. '<a href="https://daniel-rudolf.de/">Daniel Rudolf</a>.<br>It is free and open source software '
					. 'released under the <a href="https://github.com/nextcloud/cms_pico/blob/master/LICENSE">GNU '
					. 'Affero General Public License</a>.'
		)); ?>
	</p>
	<p>
		<strong>Pico <?php p($_['picoVersion']); ?></strong>
		– <a href="http://picocms.org/">http://picocms.org/</a><br>
		<?php print_unescaped($l->t(
			'Pico was made by <a href="https://gilbitron.me/">Gilbert Pellegrom</a> and '
					. '<a href="https://daniel-rudolf.de/">Daniel Rudolf</a> and is maintained by '
					. '<a href="https://github.com/picocms/Pico/graphs/contributors">The Pico Community</a>.<br>'
					. 'It is free and open source software released under the '
					. '<a href="https://github.com/picocms/Pico/blob/master/LICENSE.md">MIT license</a>.'
		)); ?>
	</p>
</article>
