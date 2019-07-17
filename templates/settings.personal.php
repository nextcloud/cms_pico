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

script(Application::APP_NAME, [ 'pico', 'personal' ]);
style(Application::APP_NAME, 'pico');

?>

<article class="section">
	<h2><?php p($l->t('Pico CMS for Nextcloud')); ?></h2>
	<p class="settings-hint"><?php p($l->t(
		'Create and publish your own websites - with Pico CMS for Nextcloud!'
	)); ?></p>

	<div class="message large">
		<div class="icon icon-info"></div>
		<div>
			<p><?php p($l->t(
				'Pico CMS for Nextcloud allows you to … TODO bla blubb'
			)); ?></p>

			<p><?php p($l->t('You will be able to access your websites using URLs like the following:')); ?>
			<p class="followup indent"><a><?php p($_['exampleFullUrl']); ?></a></p>

			<p><?php p($l->t(
					'If your Nextcloud admin configured the webserver appropriately, you might also use URLs like '
					. 'the following. If you get an "404 Not Found" error, try the longer URL shown above.'
				)); ?>
			<p class="followup indent"><a><?php p($_['exampleProxyUrl']); ?></a></p>
		</div>
	</div>

	<div id="picocms-websites" class="picocms-website-list"
			data-route="/apps/cms_pico/personal/websites"
			data-template="#picocms-websites-template"
			data-item-template="#picocms-websites-template-item"
			data-loading-template="#picocms-websites-template-loading"
			data-error-template="#picocms-websites-template-error"
			data-website-base-url="<?php p($_['baseUrl']); ?>">
		<div class="app-content-loading message large">
			<div class="icon loading"></div>
			<div>
				<p><?php p($l->t('Loading websites…')); ?></p>
			</div>
		</div>
	</div>

	<script id="picocms-websites-template" type="text/template"
			data-replaces="#picocms-websites">
		<table class="table">
			<thead>
				<tr>
					<th class="name-column"><?php p($l->t('Name')); ?></th>
					<th class="path-column"><?php p($l->t('Path')); ?></th>
					<th class="theme-column"><?php p($l->t('Theme')); ?></th>
					<th class="created-column"><?php p($l->t('Created')); ?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</script>

	<script id="picocms-websites-template-item" type="text/template"
			data-append-to="#picocms-websites > table > tbody">
		<tr>
			<td class="name-column">
				<div class="name-container">
					<div>
						<p>{name}</p>
					</div>
					<div class="actions">
						<a class="action action-open has-tooltip" title="<?php p($l->t('Open website')); ?>">
							<span class="icon-link"></span>
							<span class="hidden-visually"><?php p($l->t('Open website')); ?></span>
						</a>
						<a class="action action-files has-tooltip"
								title="<?php p($l->t('Open website directory')); ?>">
							<span class="icon-files-dark"></span>
							<span class="hidden-visually"><?php p($l->t('Open website directory')); ?></span>
						</a>
						<a class="action action-private has-tooltip"
								title="<?php p($l->t('Toggle private website')); ?>">
							<span class="icon-lock-open"></span>
							<span class="hidden-visually"><?php p($l->t('Toggle private website')); ?></span>
						</a>
						<a class="action action-delete has-tooltip"
								title="<?php p($l->t('Delete website')); ?>">
							<span class="icon-delete"></span>
							<span class="hidden-visually"><?php p($l->t('Delete website')); ?></span>
						</a>
					</div>
					<div class="more">
						<div class="icon-more"></div>
						<span class="hidden-visually"><?php p($l->t('Actions')); ?></span>

						<div class="popovermenu">
							<ul>
								<li>
									<a class="action-open">
										<span class="icon-link"></span>
										<span><?php p($l->t('Open website')); ?></span>
									</a>
								</li>
								<li>
									<a class="action-files">
										<span class="icon-files-dark"></span>
										<span><?php p($l->t('Open website directory')); ?></span>
									</a>
								</li>
								<li>
									<a class="action-private">
										<span class="icon-lock-open"></span>
										<span><?php p($l->t('Toggle private website')); ?></span>
									</a>
								</li>
								<li>
									<span class="menuitem">
										<span class="icon icon-paint-roller"></span>
										<select class="action-theme">
											<?php foreach ($_['themes'] as $theme) { ?>
												<option name="<?php p($theme); ?>"><?php p($theme); ?></option>
											<?php } ?>
										</select>
									</span>
								</li>
								<li>
									<a class="action-delete">
										<span class="icon-delete"></span>
										<span><?php p($l->t('Delete website')); ?></span>
									</a>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td class="path-column">
				<a class="action action-files has-tooltip" title="<?php p($l->t('Open website directory')); ?>">
					<span class="icon-files-dark"></span>
					<span class="hidden-visually"><?php p($l->t('Open website directory')); ?>:</span>
					{path}
				</a>
			</td>
			<td class="theme-column">
				<select class="action-theme">
					<?php foreach ($_['themes'] as $theme) { ?>
						<option name="<?php p($theme); ?>"><?php p($theme); ?></option>
					<?php } ?>
				</select>
			</td>
			<td class="created-column">
				<span class="live-relative-timestamp" data-timestamp="{creation}"></span>
			</td>
		</tr>
	</script>

	<script id="picocms-websites-template-loading" type="text/template"
			data-replaces="#picocms-websites">
		<div class="app-content-loading message large">
			<div class="icon loading"></div>
			<div>
				<p><?php p($l->t('Loading websites…')); ?></p>
			</div>
		</div>
	</script>

	<script id="picocms-websites-template-error" type="text/template"
			data-replaces="#picocms-websites">
		<div class="app-content-error message large">
			<div class="icon icon-error-color"></div>
			<div>
				<p><?php p($l->t(
					'A unexpected error occured while performing this action. Please check Nextcloud\'s logs.'
				)); ?></p>
			</div>
		</div>
	</script>
</article>

<article class="section">
	<h2><?php p($l->t('Create a new website')); ?></h2>
	<p class="settings-hint"><?php p($l->t(
		'Just fill the form below to create your own personal website.'
	)); ?></p>

	<div id="picocms-website-form" class="picocms-website-form"
			data-route="/apps/cms_pico/personal/websites"
			data-website-base-url="<?php p($_['baseUrl']); ?>"
			data-website-list="#picocms-websites">
		<form class="form">
			<fieldset>
				<div class="label">
					<label for="picocms-website-new-name"><?php p($l->t('Name')); ?></label>
				</div>
				<div class="content">
					<input id="picocms-website-new-name" class="input input-name" type="text" name="name" value=""
							placeholder="<?php p($l->t('My example website')); ?>"
							minlength="<?php p($_['nameLengthMin']); ?>"
							maxlength="<?php p($_['nameLengthMax']); ?>" />
					<p class="note">
						<?php p($l->t(
							'Here you can specify the name of your personal website. Your website\'s name will be '
							. 'used as website title, often shown in your website\'s header.'
						)); ?>
					</p>
					<div class="message input-error">
						<div class="icon icon-error-color"></div>
						<div>
							<p class="input-name-error"></p>
						</div>
					</div>
				</div>
			</fieldset>

			<fieldset>
				<div class="label">
					<label for="picocms-website-new-site"><?php p($l->t('Identifier')); ?></label>
				</div>
				<div class="content">
					<input id="picocms-website-new-site" class="input input-site" type="text" name="site" value=""
							placeholder="<?php p($l->t('example_site')); ?>"
							minlength="<?php p($_['siteLengthMin']); ?>"
							maxlength="<?php p($_['siteLengthMax']); ?>"
							pattern="<?php p($_['siteRegex']); ?>" />
					<p class="note">
						<?php p($l->t(
							'The identifier of your website prescribes both your website\'s address (URL) and the '
							. 'name of the directory your website\'s files (both pages and assets) will be stored in. '
							. 'A website\'s identifier must consist of lowercase alphanumeric characters, dashes and '
							. 'underscores (a-z, 0-9, - and _) only.'
						)); ?>
					</p>
					<div class="message input-error">
						<div class="icon icon-error-color"></div>
						<div>
							<p class="input-site-error"></p>
						</div>
					</div>
				</div>
			</fieldset>

			<fieldset>
				<div class="label">
					<label for="picocms-website-new-address"><?php p($l->t('Address')); ?></label>
				</div>
				<div class="content">
					<a id="picocms-website-new-address" class="input input-address">
						<?php p($_['baseUrl']); ?>
					</a>
					<p class="note">
						<?php p($l->t(
							'You will be able to access your website using the address (URL) shown above. If your '
							. 'Nextcloud admin configured the webserver appropriately, you might also use the shorter '
							. 'URL scheme as shown in the examples above.'
						)); ?>
					</p>
				</div>
			</fieldset>

			<fieldset>
				<div class="label">
					<label for="picocms-website-new-path"><?php p($l->t('Path')); ?></label>
				</div>
				<div class="content">
					<input id="picocms-website-new-path" class="input input-path" type="button" name="path" value="/" />
					<p class="note">
						<?php p($l->t(
							'When creating a new website, Pico CMS for Nextcloud copies the website '
							. 'template to the following new directory in your Nextcloud.'
						)); ?>
					</p>
					<div class="message input-error">
						<div class="icon icon-error-color"></div>
						<div>
							<p class="input-path-error"></p>
						</div>
					</div>
				</div>
			</fieldset>

			<fieldset>
				<div class="label">
					<label for="picocms-website-new-theme"><?php p($l->t('Theme')); ?></label>
				</div>
				<div class="content">
					<select id="picocms-website-new-theme" class="input input-theme" name="theme">
						<?php foreach ($_['themes'] as $theme) { ?>
							<option name="<?php p($theme); ?>"><?php p($theme); ?></option>
						<?php } ?>
					</select>
					<p class="note">
						<?php p($l->t(
							'You can use one of the provided themes for some greater individuality and '
							. 'style. If you want to use another theme, ask your Nextcloud admin - it might '
							. 'be possible to add your favourite theme, too!'
						)); ?>
					</p>
					<div class="message input-error">
						<div class="icon icon-error-color"></div>
						<div>
							<p class="input-theme-error"></p>
						</div>
					</div>
				</div>
			</fieldset>

			<fieldset>
				<div class="label">
					<label for="picocms-website-new-template"><?php p($l->t('Template')); ?></label>
				</div>
				<div class="content">
					<select id="picocms-website-new-template" class="input input-template" name="template">
						<?php foreach ($_['templates'] as $template) { ?>
							<option name="<?php p($template); ?>"><?php p($template); ?></option>
						<?php } ?>
					</select>
					<p class="note">
						<?php p($l->t(
							'Templates act as a starting point when creating a new website. All templates '
							. 'consist of a "content" directory (for your pages) and a "assets" directory '
							. '(for your website\'s assets), which will be copied to the above folder in '
							. 'your Nextcloud.'
						)); ?>
					</p>
					<div class="message input-error">
						<div class="icon icon-error-color"></div>
						<div>
							<p class="input-template-error"></p>
						</div>
					</div>
				</div>
			</fieldset>

			<fieldset>
				<div class="content">
					<div class="message input-error">
						<div class="icon icon-error-color"></div>
						<div>
							<p class="input-unknown-error"></p>
						</div>
					</div>

					<input class="form-submit" type="submit"
							value="<?php p($l->t('Create new website')); ?>" />
					<button class="form-submit-loading icon-loading" disabled="disabled">
						<?php p($l->t('Loading...')); ?>
					</button>
				</div>
			</fieldset>
		</form>
	</div>
</article>
