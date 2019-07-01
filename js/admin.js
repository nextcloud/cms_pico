/*
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

(function (document, $, OC, OCA) {
	'use strict';

	/**
	 * @constructor
	 *
	 * @param jQuery $element
	 */
	var CMSPicoAdmin = function ($element) {
		this.$element = $element;

		this.route = $element.data('route') || '/apps/cms_pico/admin';

		this.$template = $($element.data('template'));
		this.$systemTemplate = $($element.data('systemTemplate'));
		this.$customTemplate = $($element.data('customTemplate'));
		this.$newTemplate = $($element.data('newTemplate'));
		this.$loadingTemplate = $($element.data('loadingTemplate'));
		this.$errorTemplate = $($element.data('errorTemplate'));

		this.newButton = $element.data('new-button') || '';
		this.newItem = $element.data('new-item') || '';
		this.reloadButton = $element.data('reload-button') || '';
		this.reloadItemButton = $element.data('reload-item-button') || '';
		this.deleteItemButton = $element.data('delete-item-button') || '';

		this.reload();
	};

	/**
	 * @private
	 *
	 * @param string method
	 * @param object data
	 */
	function _api(method, data) {
		var that = this;

		_content.call(this, this.$loadingTemplate);

		$.ajax({
			method: method,
			url: OC.generateUrl(that.route),
			data: data
		}).done(function (data, textStatus, jqXHR) {
			that.update(data.systemItems, data.customItems, data.newItems);
		}).fail(function (jqXHR, textStatus, errorThrown) {
			_content.call(that, that.$errorTemplate);
		});
	}

	/**
	 * @private
	 *
	 * @param jQuery $template
	 * @param object vars
	 *
	 * @return jQuery
	 */
	function _content($template, vars) {
		var replaceContent = !!$template.data('replaces'),
			$baseElement = $($template.data('replaces') || $template.data('appendTo') || this.$element),
			$content = $template.octemplate(vars || {});

		if (replaceContent) {
			$baseElement.empty();
		}

		$content.appendTo($baseElement);

		return $content;
	}

	/**
	 * @private
	 *
	 * @return void
	 */
	function _setup() {
		var that = this;

		this.$element.find('[data-toggle="tooltip"]').tooltip();

		this.$element.find(this.newButton).on('click.CMSPicoAdmin', function () {
			_api.call(that, 'PUT', { item: that.$element.find(that.newItem).val() });
		});

		this.$element.find(this.reloadButton).on('click.CMSPicoAdmin', function () {
			that.reload();
		});
	}

	/**
	 * @private
	 *
	 * @param string name
	 * @param jQuery $item
	 *
	 * @return void
	 */
	function _setupItem(name, $item) {
		var that = this;

		$item.find(this.reloadItemButton).on('click.CMSPicoAdmin', function () {
			_api.call(that, 'UPDATE', { item: name });
		});

		$item.find(this.deleteItemButton).on('click.CMSPicoAdmin', function () {
			_api.call(that, 'DELETE', { item: name });
		});
	}

	$.extend(CMSPicoAdmin.prototype, {
		/**
		 * @return void
		 */
		reload: function () {
			_api.call(this, 'GET', {});
		},

		/**
		 * @param string[] systemItems
		 * @param string[] customItems
		 * @param string[] newItems
		 *
		 * @return void
		 */
		update: function (systemItems, customItems, newItems) {
			_content.call(this, this.$template);

			for (var i = 0, systemItem; i < systemItems.length; i++) {
				systemItem = _content.call(this, this.$systemTemplate, { name: systemItems[i] });
				_setupItem.call(this, systemItems[i], systemItem);
			}

			for (var j = 0, customItem; j < customItems.length; j++) {
				customItem = _content.call(this, this.$customTemplate, { name: customItems[j] });
				_setupItem.call(this, customItems[j], customItem);
			}

			for (var k = 0, newItem; k < newItems.length; k++) {
				newItem = _content.call(this, this.$newTemplate, { name: newItems[k] });
				_setupItem.call(this, newItems[k], newItem);
			}

			_setup.call(this);
		}
	});

	if (!OCA.CMSPico) {
		OCA.CMSPico = {};
	}

	OCA.CMSPico.Admin = CMSPicoAdmin;

	$('.picocms-admin').each(function () {
		var $this = $(this);
		$this.data('CMSPicoAdmin', new OCA.CMSPico.Admin($this));
	});
})(document, jQuery, OC, OCA);
