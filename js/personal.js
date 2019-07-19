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

/** global: OC */
/** global: OCA */
/** global: jQuery */

(function (document, $, OC, OCA) {
	'use strict';

	/** @constant {number} */
	var WEBSITE_TYPE_PUBLIC = 1;

	/** @constant {number} */
	var WEBSITE_TYPE_PRIVATE = 2;

	/**
	 * @class
	 * @extends OCA.CMSPico.List
	 *
	 * @param {jQuery}        $element
	 * @param {Object}        [options]
	 * @param {string}        [options.route]
	 * @param {jQuery|string} [options.template]
	 * @param {jQuery|string} [options.itemTemplate]
	 * @param {jQuery|string} [options.loadingTemplate]
	 * @param {jQuery|string} [options.errorTemplate]
	 * @param {string}        [options.websiteBaseUrl]
	 */
	OCA.CMSPico.WebsiteList = function ($element, options) {
		this.initialize($element, options);
	};

	/**
	 * @lends OCA.CMSPico.WebsiteList.prototype
	 */
	OCA.CMSPico.WebsiteList.prototype = $.extend({}, OCA.CMSPico.List.prototype, {
		/** @member {jQuery} */
		$itemTemplate: $(),

		/** @member {string} */
		websiteBaseUrl: '',

		/**
		 * @constructs
		 *
		 * @param {jQuery}        $element
		 * @param {Object}        [options]
		 * @param {string}        [options.route]
		 * @param {jQuery|string} [options.template]
		 * @param {jQuery|string} [options.itemTemplate]
		 * @param {jQuery|string} [options.loadingTemplate]
		 * @param {jQuery|string} [options.errorTemplate]
		 * @param {string}        [options.websiteBaseUrl]
		 */
		initialize: function ($element, options) {
			OCA.CMSPico.List.prototype.initialize.apply(this, arguments);

			options = $.extend({
				itemTemplate: $element.data('itemTemplate'),
				websiteBaseUrl: $element.data('websiteBaseUrl')
			}, options);

			this.$itemTemplate = $(options.itemTemplate);
			this.websiteBaseUrl = options.websiteBaseUrl + ((options.websiteBaseUrl.substr(-1) !== '/') ? '/' : '');

			var signature = 'OCA.CMSPico.WebsiteList.initialize()';
			if (!this.$itemTemplate.length) throw signature + ': No valid item template given';
			if (this.websiteBaseUrl === '/') throw signature + ': No valid website base URL given';

			this._init();
		},

		/**
		 * @public
		 *
		 * @param {Object}   data
		 * @param {Object[]} data.websites
		 * @param {int}      data.websites[].id
		 * @param {string}   data.websites[].user_id
		 * @param {string}   data.websites[].name
		 * @param {string}   data.websites[].site
		 * @param {string}   data.websites[].theme
		 * @param {int}      data.websites[].type
		 * @param {Object}   data.websites[].options
		 * @param {string}   [data.websites[].options.private]
		 * @param {string}   data.websites[].path
		 * @param {int}      data.websites[].creation
		 */
		update: function (data) {
			this._content(this.$template);

			for (var i = 0, $website; i < data.websites.length; i++) {
				$website = this._content(this.$itemTemplate, data.websites[i]);
				this._setupItem($website, data.websites[i]);
			}

			this._setup();
		},

		/**
		 * @private
		 */
		_init: function () {
			var that = this;
			$(document).on('click.CMSPicoWebsiteList', function (event) {
				var $target = $(event.target),
						$menu;

				if ($target.is('.icon-more')) {
					$menu = $target.nextAll('.popovermenu');
					if ($menu.length) {
						$menu.toggleClass('open');
					}
				} else {
					// if clicked inside the menu, don't close it
					$menu = $target.closest('.popovermenu');
				}

				that.$element.find('.popovermenu.open').not($menu).removeClass('open');
			});
		},

		/**
		 * @protected
		 */
		_setup: function () {
			this.$element.find('.has-tooltip').tooltip();

			this.$element.find('.live-relative-timestamp').each(function() {
				var $this = $(this),
					time = parseInt($this.data('timestamp'), 10) * 1000;

				$this
					.data('timestamp', time)
					.text(OC.Util.relativeModifiedDate(time))
					.addClass('has-tooltip')
					.tooltip({ title: OC.Util.formatDate(time) });
			});
		},

		/**
		 * @protected
		 *
		 * @param {jQuery} $website
		 * @param {Object} websiteData
		 * @param {int}    websiteData.id
		 * @param {string} websiteData.user_id
		 * @param {string} websiteData.name
		 * @param {string} websiteData.site
		 * @param {string} websiteData.theme
		 * @param {int}    websiteData.type
		 * @param {Object} websiteData.options
		 * @param {string} [websiteData.options.private]
		 * @param {string} websiteData.path
		 * @param {int}    websiteData.creation
		 */
		_setupItem: function ($website, websiteData) {
			var that = this;

			// open website
			var websiteUrl = this.websiteBaseUrl + websiteData.site;
			this._clickRedirect($website.find('.action-open'), websiteUrl);

			// open website directory
			var filesUrl = OC.generateUrl('/apps/files/') + '?dir=' + OC.encodePath(websiteData.path);
			this._clickRedirect($website.find('.action-files'), filesUrl);

			// toggle private website
			$website.find('.action-private').each(function () {
				var $this = $(this),
					$icon = $this.find('[class^="icon-"], [class*=" icon-"]'),
					value = (websiteData.type === WEBSITE_TYPE_PRIVATE);

				$icon
					.addClass(value ? 'icon-lock' : 'icon-lock-open')
					.removeClass(value ? 'icon-lock-open' : 'icon-lock');

				$this.data('value', value);

				$this.on('click.CMSPicoWebsiteList', function (event) {
					event.preventDefault();

					var websiteType = $(this).data('value') ? WEBSITE_TYPE_PUBLIC : WEBSITE_TYPE_PRIVATE;
					that._updateItem(websiteData.id, { type: websiteType });
				});
			});

			// change website theme
			$website.find('.action-theme').each(function () {
				var $this = $(this);

				$this.val(websiteData.theme);

				$this.on('change.CMSPicoWebsiteList', function (event) {
					event.preventDefault();
					that._updateItem(websiteData.id, { theme: $(this).val() });
				});
			});

			// delete website
			$website.find('.action-delete').on('click.CMSPicoWebsiteList', function (event) {
				event.preventDefault();

				var dialogTitle = t('cms_pico', 'Confirm website deletion'),
					dialogText = t('cms_pico', 'This operation will delete the website "{name}". However, all of ' +
							'its contents will still be available in your Nextcloud.', { name: websiteData.name });

				OC.dialogs.confirm(dialogText, dialogTitle, function (result) {
					if (result) {
						that._api('DELETE', '' + websiteData.id);
					}
				});
			});
		},

		/**
		 * @private
		 *
		 * @param {jQuery} $elements
		 * @param {string} url
		 */
		_clickRedirect: function ($elements, url) {
			$elements.each(function () {
				var $element = $(this);

				if ($element.is('a')) {
					$element.attr('href', url);
				} else {
					$element.on('click.CMSPicoWebsiteList', function (event) {
						event.preventDefault();
						OC.redirect(url);
					});
				}
			});
		},

		/**
		 * @private
		 *
		 * @param {number} item
		 * @param {Object} data
		 */
		_updateItem: function (item, data) {
			this._api('POST', '' + item, { data: data });
		}
	});

	$('.picocms-website-list').each(function () {
		var $this = $(this),
			websiteList = new OCA.CMSPico.WebsiteList($this);

		$this.data('CMSPicoWebsiteList', websiteList);
		websiteList.reload();
	});

	/**
	 * @class
	 *
	 * @param {jQuery} $element
	 * @param {Object} [options]
	 * @param {string} [options.route]
	 */
	OCA.CMSPico.WebsiteForm = function ($element, options) {
		this.initialize($element, options);
	};

	/**
	 * @lends OCA.CMSPico.WebsiteForm.prototype
	 */
	OCA.CMSPico.WebsiteForm.prototype = {
		/** @member {jQuery} */
		$element: $(),

		/** @member {string} */
		route: '',

		/**
		 * @constructs
		 *
		 * @param {jQuery} $element
		 * @param {Object} [options]
		 * @param {string} [options.route]
		 */
		initialize: function ($element, options) {
			this.$element = $element;

			options = $.extend({
				route: $element.data('route')
			}, options);

			this.route = options.route;

			var signature = 'OCA.CMSPico.WebsiteForm.initialize()';
			if (!this.route) throw signature + ': No route given';
		},

		/**
		 * @public
		 */
		prepare: function () {
			var that = this,
				$form = this.$element.find('form'),
				$site = $form.find('.input-site'),
				$path = $form.find('.input-path');

			this._inputSite($site);

			$site.on('input.CMSPicoWebsiteForm', function (event) {
				that._inputSite($(this));
			});

			$path.on('click.CMSPicoWebsiteForm', function (event) {
				event.preventDefault();

				OC.dialogs.filepicker(
					t('cms_pico', 'Choose website directory'),
					function (path, type) {
						$path.val(path + '/' + $site.val());
					},
					false,
					'httpd/unix-directory',
					true
				);
			});

			$form.on('submit.CMSPicoWebsiteForm', function (event) {
				event.preventDefault();
				that.submit();
			});
		},

		/**
		 * @public
		 */
		submit: function () {
			var $form = this.$element.find('form'),
				$submitButton = this.$element.find('.form-submit'),
				$loadingButton = this.$element.find('.form-submit-loading'),
				that = this,
				data = {};

			$form.find(':input').each(function () {
				var name = this.name,
					value = $(this).val();

				if (name) {
					data[name] = value;
				}
			});

			$form.find('fieldset.form-error')
				.removeClass('form-error');

			$submitButton.hide();
			$loadingButton.show();

			$.ajax({
				method: 'POST',
				url: OC.generateUrl(this.route),
				data: { data: data }
			}).done(function (data, textStatus, jqXHR) {
				that._success(data);
			}).fail(function (jqXHR, textStatus, errorThrown) {
				that._error(jqXHR.responseJSON || {});

				$submitButton.show();
				$loadingButton.hide();
			});
		},

		/**
		 * @private
		 *
		 * @param {jQuery}              $element
		 * @param {string|number|Array} [value]
		 *
		 * @returns {jQuery|string|number|Array}
		 */
		_val: function ($element, value) {
			if (value === undefined) {
				return $element.is(':input') ? $element.val() : $element.text();
			} else {
				return $element.is(':input') ? $element.val(value) : $element.text(value);
			}
		},

		/**
		 * @private
		 *
		 * @param {jQuery} $site
		 */
		_inputSite: function ($site) {
			var $form = this.$element.find('form'),
				$address = $form.find('.input-address'),
				$path = $form.find('.input-path'),
				value = this._val($site);

			this._val($address, OC.dirname(this._val($address)) + '/' + value);
			this._val($path, OC.dirname(this._val($path)) + '/' + value);
		},

		/**
		 * @private
		 *
		 * @param {Object} data
		 */
		_success: function (data) {
			OC.reload();
		},

		/**
		 * @private
		 *
		 * @param {Object} data
		 */
		_error: function (data) {
			if (data.form_error) {
				var $errorInput = this.$element.find('.input-' + data.form_error.field + '-error');

				if ($errorInput.length) {
					$errorInput.closest('fieldset').addClass('form-error');
					$errorInput.text(data.form_error.message);
					return;
				}
			}

			var $formError = this.$element.find('.input-unknown-error'),
				message = t('cms_pico', 'A unexpected error occured while performing this action. ' +
						'Please check Nextcloud\'s logs.');

			$formError.closest('fieldset').addClass('form-error');
			$formError.text(message);
		}
	};

	$('.picocms-website-form').each(function () {
		var $this = $(this),
			websiteForm = new OCA.CMSPico.WebsiteForm($this);

		$this.data('CMSPicoWebsiteForm', websiteForm);
		websiteForm.prepare();
	})
})(document, jQuery, OC, OCA);
