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
			<p><?php print_unescaped($l->t(
				'<a href="https://apps.nextcloud.com/apps/cms_pico">Pico CMS for Nextcloud</a> combines the power of '
				. '<a href="http://picocms.org/"><strong>Pico</strong></a> and Nextcloud to create simple, secure, '
				. 'shareable and amazingly powerful websites with just a few clicks. Pico is a stupidly simple, '
				. 'blazing fast, flat file CMS - making the web easy!'
			)); ?></p>

			<p><?php print_unescaped($l->t(
				'Start a blog, share your resume with the world, create a plan for world domination and only share '
				. 'it with the right friends or build a knowledge base and let the smart ones among your colleagues '
				. 'help out. Pico CMS for Nextcloud allows you to create and manage your own websites. Creating a new '
				. 'page with Pico is no more than creating a simple text file in your Nextcloud files. No config is '
				. 'required, no utterly complex management interfaces - just files. It\'s the perfect match with '
				. 'Nextcloud. Secure Sharing, Collaboration, Access Control - not just for your files, but also your '
				. 'websites, all made possible by Pico CMS for Nextcloud! Breaking the boundaries between your Mobile '
				. '& Desktop devices and your Server.'
			)); ?></p>

			<p><?php print_unescaped($l->t(
				'A website consist of just a bunch of <code class="inline">.md</code> text files in your Nextcloud '
				. 'files. <code class="inline">.md</code> stands for <a href="https://www.markdownguide.org/"><strong>'
				. 'Markdown</strong></a> - a super simple and intuitive markup to create headings, paragraphs, text '
				. 'formatting, lists, images and links. But don\'t despair - you don\'t have to learn yet another '
				. 'language if you don\'t want to. Try Nextcloud\'s <a href="https://apps.nextcloud.com/apps/text">'
				. 'Text</a> or <a href="https://apps.nextcloud.com/apps/files_markdown">Markdown Editor</a> apps to '
				. 'make easy things stupidly simple. But what about meta data like a page\'s title or release date? '
				. 'Guess right, it\'s all in one place. At the top of your Markdown files you can place a block with '
				. 'such meta data - called the <a href="https://en.wikipedia.org/wiki/YAML"><strong>YAML</strong></a> '
				. 'Front Matter. Creating websites can\'t be easier…'
			)); ?></p>

			<p><?php print_unescaped($l->t(
				'You want to learn more about Pico CMS for Nextcloud? Easy! Just create your first personal website '
				. 'using the "sample_pico" template. Pico\'s sample contents will explain all you need to know…'
			)); ?></p>

			<p><?php p($l->t('You will be able to access your websites using URLs like the following:')); ?>
			<p class="followup indent"><a><?php p(rtrim($_['baseUrl'], '/') . '/' . $_['exampleSite']); ?></a></p>
		</div>
	</div>

	<?php if ($_['limitedUser']) { ?>
		<div class="message large error">
			<div class="icon icon-error-color"></div>
			<div>
				<p><?php p($l->t(
					'The Nextcloud admin limited access of Pico CMS for Nextcloud to certain groups. Unfortunately '
					. 'you don\'t have permission to create personal websites. You can still access websites of other '
					. 'users, possibly including private websites. If you had permission to create websites in the '
					. 'past, you don\'t have to worry about your data: Nothing is lost. However, nobody will be able '
					. 'to access your private websites and a "Website not found" error is shown instead.'
				)); ?></p>
			</div>
		</div>
	<?php } ?>

	<div id="picocms-websites" class="picocms-website-list"
			data-route="/apps/cms_pico/personal/websites"
			data-template="#picocms-websites-template"
			data-item-template="#picocms-websites-template-item"
			data-private-settings-template="#picocms-websites-template-private-settings"
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
						<a class="action action-open has-tooltip" title="<?php p($l->t('Go to website')); ?>">
							<span class="icon-link"></span>
							<span class="hidden-visually"><?php p($l->t('Go to website')); ?></span>
						</a>
						<a class="action action-files has-tooltip"
								title="<?php p($l->t('Go to website directory')); ?>">
							<span class="icon-files-dark"></span>
							<span class="hidden-visually"><?php p($l->t('Go to website directory')); ?></span>
						</a>
						<a class="action action-private has-tooltip"
								title="<?php p($l->t('Edit private website settings')); ?>">
							<span class="icon-lock-open"></span>
							<span class="hidden-visually"><?php p($l->t('Edit private website settings')); ?></span>
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
										<span><?php p($l->t('Go to website')); ?></span>
									</a>
								</li>
								<li>
									<a class="action-files">
										<span class="icon-files-dark"></span>
										<span><?php p($l->t('Go to website directory')); ?></span>
									</a>
								</li>
								<li>
									<a class="action-private">
										<span class="icon-lock-open"></span>
										<span><?php p($l->t('Edit private website settings')); ?></span>
									</a>
								</li>
								<li>
									<span class="menuitem">
										<span class="icon icon-paint-roller"></span>
										<select class="action-theme">
											<?php foreach ($_['themes'] as $themeData) { ?>
												<?php if ($themeData['compat']) { ?>
													<option value="<?php p($themeData['name']); ?>">
														<?php p($themeData['name']); ?>
													</option>
												<?php } ?>
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
				<a class="action action-files has-tooltip" title="<?php p($l->t('Go to website directory')); ?>">
					<span class="icon-files-dark"></span>
					<span class="hidden-visually"><?php p($l->t('Go to website directory')); ?>:</span>
					{path}
				</a>
			</td>
			<td class="theme-column">
				<select class="action-theme">
					<?php foreach ($_['themes'] as $themeData) { ?>
						<?php if ($themeData['compat']) { ?>
							<option value="<?php p($themeData['name']); ?>"><?php p($themeData['name']); ?></option>
						<?php } ?>
					<?php } ?>
				</select>
			</td>
			<td class="created-column">
				<span class="live-relative-timestamp" data-timestamp="{creation}"></span>
			</td>
		</tr>
	</script>

	<script id="picocms-websites-template-private-settings" type="text/template">
		<form id="{id}" title="{title}" class="form">
			<p class="dialog-hint"><?php p($l->t(
				'Pico CMS for Nextcloud supports both public and private websites. Everyone can access public '
				. 'websites, no matter whether they are logged in or not. If you want to limit access to a certain '
				. 'subset of users, create a private website. All visitors of a private website must be logged in, '
				. 'otherwise a "Access forbidden" error is shown. Additionally one of the following conditions must '
				. 'be met: (1) the user has access to the website\'s source files (i.e. the source folder is shared '
				. 'with the user), (2) the user is a member of one of the groups listed below, or (3) the user is a '
				. 'member of one of the groups specified in the YAML header of the requested page using the "access" '
				. 'meta value.'
			)); ?></p>
			<fieldset>
				<div class="label">
					<span><?php p($l->t('Website type')); ?></span>
				</div>
				<div class="content">
					<p>
						<input type="radio" id="picocms-websites-private-public" class="radio input-private-public"
								name="type" value="<?php p($_['typePublic']); ?>">
						<label for="picocms-websites-private-public">
							<?php p($l->t('Public website')); ?>
							<span class="note">– <?php p($l->t(
								'The website is publicly accessible and requires no authentication whatsoever.'
							)); ?></span>
						</label>
					</p>
					<p>
						<input type="radio" id="picocms-websites-private-private" class="radio input-private-private"
								name="type" value="<?php p($_['typePrivate']); ?>">
						<label for="picocms-websites-private-private">
							<?php p($l->t('Private website')); ?>
							<span class="note">– <?php p($l->t(
								'The website requires authentication, access is limited to a subset of all users.'
							)); ?></span>
						</label>
					</p>
				</div>
			</fieldset>
			<fieldset>
				<div class="label">
					<label class="select2-align" for="picocms-websites-private-groups">
						<?php p($l->t('Group access')); ?>
					</label>
				</div>
				<div class="content">
					<input type="hidden" class="input input-private-groups select2-placeholder"
							name="options[group_access]" value="" />
					<div class="message input select2-loading">
						<div class="icon icon-loading"></div>
						<div>
							<p><?php p($l->t('Loading groups…')); ?></p>
						</div>
					</div>
					<p class="note"><?php p($l->t(
						'Grant access to all members of the selected groups.'
					)); ?></p>
				</div>
			</fieldset>
		</form>
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

<?php if (!$_['limitedUser']) { ?>
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
								placeholder="<?php p($_['exampleSite']); ?>"
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
								'You will be able to access your website using the address (URL) shown above.'
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
							<?php foreach ($_['themes'] as $themeData) { ?>
								<?php if ($themeData['compat']) { ?>
									<option value="<?php p($themeData['name']); ?>"><?php p($themeData['name']); ?></option>
								<?php } ?>
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
							<?php foreach ($_['templates'] as $templateData) { ?>
								<?php if ($templateData['compat']) { ?>
									<option value="<?php p($templateData['name']); ?>"><?php p($templateData['name']); ?></option>
								<?php } ?>
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
							<?php p($l->t('Loading…')); ?>
						</button>
					</div>
				</fieldset>
			</form>
		</div>
	</article>
<?php } ?>
