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
		/** @member {jQuery} */
		$systemTemplate: $(),

		/** @member {jQuery} */
		$customTemplate: $(),

		/** @member {jQuery} */
		$newTemplate: $(),

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
		 * @param {jQuery|string} [options.loadingTemplate]
		 * @param {jQuery|string} [options.errorTemplate]
		 */
		initialize: function ($element, options) {
			OCA.CMSPico.List.prototype.initialize.apply(this, arguments);

			options = $.extend({
				systemTemplate: $element.data('systemTemplate'),
				customTemplate: $element.data('customTemplate'),
				newTemplate: $element.data('newTemplate')
			}, options);

			this.$systemTemplate = $(options.systemTemplate);
			this.$customTemplate = $(options.customTemplate);
			this.$newTemplate = $(options.newTemplate);

			var signature = 'OCA.CMSPico.AdminList.initialize()';
			if (!this.$systemTemplate.length) throw signature + ': No valid system item template given';
			if (!this.$customTemplate.length) throw signature + ': No valid custom item template given';
			if (!this.$newTemplate.length) throw signature + ': No valid new item template given';
		},

		/**
		 * @public
		 *
		 * @param {Object}   data
		 * @param {string[]} data.systemItems
		 * @param {string[]} data.customItems
		 * @param {string[]} data.newItems
		 */
		update: function (data) {
			this._content(this.$template);

			for (var i = 0, $systemItem; i < data.systemItems.length; i++) {
				$systemItem = this._content(this.$systemTemplate, { name: data.systemItems[i] });
				this._setupItem(data.systemItems[i], $systemItem);
			}

			for (var j = 0, $customItem; j < data.customItems.length; j++) {
				$customItem = this._content(this.$customTemplate, { name: data.customItems[j] });
				this._setupItem(data.customItems[j], $customItem);
			}

			for (var k = 0, $newItem; k < data.newItems.length; k++) {
				$newItem = this._content(this.$newTemplate, { name: data.newItems[k] });
				this._setupItem(data.newItems[k], $newItem);
			}

			this._setup();
		},

		/**
		 * @protected
		 */
		_setup: function () {
			var that = this;

			this.$element.find('.has-tooltip').tooltip();

			this.$element.find('.action-new').on('click.CMSPicoAdminList', function (event) {
				event.preventDefault();
				that._api('POST', '', { item: that.$element.find('.action-new-item').val() });
			});

			this.$element.find('.action-reload').on('click.CMSPicoAdminList', function (event) {
				event.preventDefault();
				that.reload();
			});
		},

		/**
		 * @protected
		 *
		 * @param {string} name
		 * @param {jQuery} $item
		 */
		_setupItem: function (name, $item) {
			var that = this;

			$item.find('.action-sync').on('click.CMSPicoAdminList', function (event) {
				event.preventDefault();
				that._api('POST', name);
			});

			$item.find('.action-delete').on('click.CMSPicoAdminList', function (event) {
				event.preventDefault();
				that._api('DELETE', name);
			});
		}
	});

	$('.picocms-admin-list').each(function () {
		var $this = $(this),
			adminList = new OCA.CMSPico.AdminList($this);

		$this.data('CMSPicoAdminList', adminList);
		adminList.reload();
	});
})(document, jQuery, OC, OCA);
