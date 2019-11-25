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

	/**
	 * @class
	 * @extends OCA.CMSPico.List
	 *
	 * @param {jQuery}        $element
	 * @param {Object}        [options]
	 * @param {string}        [options.route]
	 * @param {jQuery|string} [options.template]
	 * @param {jQuery|string} [options.systemTemplate]
	 * @param {jQuery|string} [options.customTemplate]
	 * @param {jQuery|string} [options.newTemplate]
	 * @param {jQuery|string} [options.copyTemplate]
	 * @param {jQuery|string} [options.loadingTemplate]
	 * @param {jQuery|string} [options.errorTemplate]
	 */
	OCA.CMSPico.AdminList = function ($element, options) {
		this.initialize($element, options);
	};

	/**
	 * @lends OCA.CMSPico.AdminList.prototype
	 */
	OCA.CMSPico.AdminList.prototype = $.extend({}, OCA.CMSPico.List.prototype, {
		/** @member {Object[]|string[]} */
		systemItems: [],

		/** @member {Object[]|string[]} */
		customItems: [],

		/** @member {Object[]|string[]} */
		newItems: [],

		/** @member {jQuery} */
		$systemTemplate: $(),

		/** @member {jQuery} */
		$customTemplate: $(),

		/** @member {jQuery} */
		$newTemplate: $(),

		/** @member {jQuery} */
		$copyTemplate: $(),

		/**
		 * @constructs
		 *
		 * @param {jQuery}        $element
		 * @param {Object}        [options]
		 * @param {string}        [options.route]
		 * @param {jQuery|string} [options.template]
		 * @param {jQuery|string} [options.systemTemplate]
		 * @param {jQuery|string} [options.customTemplate]
		 * @param {jQuery|string} [options.newTemplate]
		 * @param {jQuery|string} [options.copyTemplate]
		 * @param {jQuery|string} [options.loadingTemplate]
		 * @param {jQuery|string} [options.errorTemplate]
		 */
		initialize: function ($element, options) {
			OCA.CMSPico.List.prototype.initialize.apply(this, arguments);

			options = $.extend({
				systemTemplate: $element.data('systemTemplate'),
				customTemplate: $element.data('customTemplate'),
				newTemplate: $element.data('newTemplate'),
				copyTemplate: $element.data('copyTemplate')
			}, options);

			this.$systemTemplate = $(options.systemTemplate);
			this.$customTemplate = $(options.customTemplate);
			this.$newTemplate = $(options.newTemplate);
			this.$copyTemplate = $(options.copyTemplate);

			var signature = 'OCA.CMSPico.AdminList.initialize()';
			if (!this.$systemTemplate.length) throw signature + ': No valid system item template given';
			if (!this.$customTemplate.length) throw signature + ': No valid custom item template given';
		},

		/**
		 * @public
		 *
		 * @param {Object}            data
		 * @param {Object[]|string[]} data.systemItems
		 * @param {Object[]|string[]} data.customItems
		 * @param {Object[]|string[]} data.newItems
		 */
		update: function (data) {
			var that = this;

			this.systemItems = data.systemItems;
			this.customItems = data.customItems;
			this.newItems = data.newItems;

			this._content(this.$template);

			$.each(data.systemItems, function (_, value) {
				var itemData = (typeof value === 'object') ? value : { name: value },
					$item = that._content(that.$systemTemplate, itemData);
				that._setupItem($item, itemData);
			});

			$.each(data.customItems, function (_, value) {
				var itemData = (typeof value === 'object') ? value : { name: value },
					$item = that._content(that.$customTemplate, itemData);
				that._setupItem($item, itemData);
			});

			$.each(data.newItems, function (_, value) {
				var itemData = (typeof value === 'object') ? value : { name: value },
					$item = that._content(that.$newTemplate, itemData);
				that._setupItem($item, itemData);
			});

			this._setup();
		},

		/**
		 * @protected
		 */
		_setup: function () {
			var $newItem = this.$element.find('.action-new-item'),
				$newItemButton = this.$element.find('.action-new'),
				that = this;

			if ($newItem.val()) {
				$newItemButton.on('click.CMSPicoAdminList', function (event) {
					event.preventDefault();
					that._api('POST', '', {item: $newItem.val()});
				});
			} else {
				$newItemButton.add($newItem).prop('disabled', true);
			}

			this.$element.find('.action-reload').on('click.CMSPicoAdminList', function (event) {
				event.preventDefault();
				that.reload();
			});
		},

		/**
		 * @protected
		 *
		 * @param {jQuery}  $item
		 * @param {Object}  itemData
		 * @param {string}  itemData.name
		 * @param {boolean} [itemData.compat]
		 * @param {string}  [itemData.compatReason]
		 * @param {Object}  [itemData.compatReasonData]
		 */
		_setupItem: function ($item, itemData) {
			var that = this;

			$item.find('.info-compat').each(function () {
				var $this = $(this),
					$icon = $this.find('[class^="icon-"], [class*=" icon-"]'),
					compat = (itemData.compat === undefined) || !!itemData.compat;

				$this.data('value', compat);

				$icon
					.addClass(compat ? 'icon-checkmark' : 'icon-error-color')
					.removeClass(compat ? 'icon-error-color' : 'icon-checkmark');

				if ($icon.hasClass('has-tooltip')) {
					var compatReason = $icon.prop('title') || '';
					if (itemData.compatReason) {
						var rawCompatReason = OCA.CMSPico.Util.unescape(itemData.compatReason);
						compatReason = t('cms_pico', rawCompatReason, itemData.compatReasonData);
					}

					$icon
						.prop('title', compatReason)
						.tooltip();
				}
			});

			$item.find('.action-sync').on('click.CMSPicoAdminList', function (event) {
				event.preventDefault();
				that._api('POST', itemData.name);
			});

			$item.find('.action-copy').each(function () {
				var $this = $(this),
					dialogId = 'picocms-dialog-copy',
					dialogTitle = $this.data('originalTitle') || $this.prop('title') || $this.text();

				var $dialog = that.$copyTemplate.octemplate({
					id: dialogId,
					title: dialogTitle,
					source: itemData.name
				});

				var dialogButtons = [
					{
						text: t('cms_pico', 'Abort'),
						click: function (event) {
							$dialog.ocdialog('close');
						}
					},
					{
						text: t('cms_pico', 'Copy'),
						defaultButton: true,
						click: function (event) {
							var value = $dialog.find('.input-name').val();
							that._api('CLONE', itemData.name, { name: value });

							$dialog.ocdialog('close');
						}
					}
				];

				$this.on('click.CMSPicoAdminList', function (event) {
					event.preventDefault();

					$('#' + dialogId).ocdialog('close');

					$('#app-content').append($dialog);
					$dialog.ocdialog({ buttons: dialogButtons });
					$dialog.find('.input-name').focus();
				});
			});

			$item.find('.action-delete').on('click.CMSPicoAdminList', function (event) {
				event.preventDefault();
				that._api('DELETE', itemData.name);
			});
		}
	});

	$('.picocms-admin-list').each(function () {
		var $this = $(this),
			adminList = new OCA.CMSPico.AdminList($this);

		$this.data('CMSPicoAdminList', adminList);
		adminList.reload();
	});

	/**
	 * @class
	 * @extends OCA.CMSPico.Form
	 *
	 * @param {jQuery} $element
	 * @param {Object} [options]
	 * @param {string} [options.route]
	 */
	OCA.CMSPico.LimitGroupsForm = function ($element, options) {
		this.initialize($element, options);
	};

	/**
	 * @lends OCA.CMSPico.LimitGroupsForm.prototype
	 */
	OCA.CMSPico.LimitGroupsForm.prototype = $.extend({}, OCA.CMSPico.Form.prototype, {
		/**
		 * @public
		 */
		prepare: function () {
			var that = this,
				$input = this.$element.find('input');

			// loading order is crucial - and Nextcloud loads its own JS settings files last... m(
			$(function () {
				OC.Settings.setupGroupsSelect($input);

				$input.on('change.CMSPicoLimitGroupsForm', function (event) {
					that.submit();
				});
			});
		},

		/**
		 * @public
		 */
		submit: function () {
			var $input = this.$element.find(':input'),
				data = this.$element.serialize();

			$input.prop('disabled', true);

			$.ajax({
				method: 'POST',
				url: OC.generateUrl(this.route),
				data: data
			}).done(function (data, textStatus, jqXHR) {
				$input.prop('disabled', false);
			});
		}
	});

	$('.picocms-limit_groups-form').each(function () {
		var $this = $(this),
			limitGroupsForm = new OCA.CMSPico.LimitGroupsForm($this);

		$this.data('CMSPicoLimitGroupsForm', limitGroupsForm);
		limitGroupsForm.prepare();
	});

	/**
	 * @class
	 * @extends OCA.CMSPico.Form
	 *
	 * @param {jQuery} $element
	 * @param {Object} [options]
	 * @param {string} [options.route]
	 */
	OCA.CMSPico.LinkModeForm = function ($element, options) {
		this.initialize($element, options);
	};

	/**
	 * @lends OCA.CMSPico.LinkModeForm.prototype
	 */
	OCA.CMSPico.LinkModeForm.prototype = $.extend({}, OCA.CMSPico.Form.prototype, {
		/**
		 * @public
		 */
		prepare: function () {
			var that = this,
				$input = this.$element.find('input[type="radio"]');

			$input.on('change.CMSPicoLinkModeForm', function (event) {
				that.submit();
			});
		},

		/**
		 * @public
		 */
		submit: function () {
			var $input = this.$element.find(':input'),
				data = this.$element.serialize();

			$input.prop('disabled', true);

			$.ajax({
				method: 'POST',
				url: OC.generateUrl(this.route),
				data: data
			}).done(function (data, textStatus, jqXHR) {
				$input.prop('disabled', false);
			});
		}
	});

	$('.picocms-link_mode-form').each(function () {
		var $this = $(this),
			linkModeForm = new OCA.CMSPico.LinkModeForm($this);

		$this.data('CMSPicoLinkModeForm', linkModeForm);
		linkModeForm.prepare();
	});
})(document, jQuery, OC, OCA);
