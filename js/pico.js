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

/** global: OC */
/** global: OCA */
/** global: jQuery */

(function (document, $, OC, OCA) {
	'use strict';

	if (!OCA.CMSPico) {
		/** @namespace OCA.CMSPico */
		OCA.CMSPico = {};
	}

	/**
	 * @class
	 *
	 * @param {jQuery}        $element
	 * @param {Object}        [options]
	 * @param {string}        [options.route]
	 * @param {jQuery|string} [options.template]
	 * @param {jQuery|string} [options.loadingTemplate]
	 * @param {jQuery|string} [options.errorTemplate]
	 */
	OCA.CMSPico.List = function ($element, options) {
		this.initialize($element, options);
	};

	/**
	 * @lends OCA.CMSPico.List.prototype
	 */
	OCA.CMSPico.List.prototype = {
		/** @member {jQuery} */
		$element: $(),

		/** @member {string} */
		route: '',

		/** @member {jQuery} */
		$template: $(),

		/** @member {jQuery} */
		$loadingTemplate: $(),

		/** @member {jQuery} */
		$errorTemplate: $(),

		/**
		 * @constructs
		 *
		 * @param {jQuery}        $element
		 * @param {Object}        [options]
		 * @param {string}        [options.route]
		 * @param {jQuery|string} [options.template]
		 * @param {jQuery|string} [options.loadingTemplate]
		 * @param {jQuery|string} [options.errorTemplate]
		 */
		initialize: function ($element, options) {
			this.$element = $element;

			options = $.extend({
				route: $element.data('route'),
				template: $element.data('template'),
				loadingTemplate: $element.data('loadingTemplate'),
				errorTemplate: $element.data('errorTemplate')
			}, options);

			this.route = options.route;
			this.$template = $(options.template);
			this.$loadingTemplate = $(options.loadingTemplate);
			this.$errorTemplate = $(options.errorTemplate);

			var signature = 'OCA.CMSPico.List.initialize()';
			if (!this.route) throw signature + ': No route given';
			if (!this.$template.length) throw signature + ': No valid list template given';
			if (!this.$loadingTemplate.length) throw signature + ': No valid loading template given';
			if (!this.$errorTemplate.length) throw signature + ': No valid error template given';
		},

		/**
		 * @public
		 */
		reload: function () {
			this._api('GET');
		},

		/**
		 * @public
		 * @abstract
		 *
		 * @param {Object} data
		 */
		update: function (data) {},

		/**
		 * @protected
		 *
		 * @param {string}         method
		 * @param {string}         [item]
		 * @param {Object}         [data]
		 * @param {function(data)} [callback]
		 */
		_api: function (method, item, data, callback) {
			var that = this,
				url = this.route + (item ? ((this.route.substr(-1) !== '/') ? '/' : '') + item : '');

			this._content(this.$loadingTemplate);

			$.ajax({
				method: method,
				url: OC.generateUrl(url),
				data: data || {}
			}).done(function (data, textStatus, jqXHR) {
				if (callback === undefined) {
					that.update(data);
				} else if (typeof callback === 'function') {
					callback(data);
				}
			}).fail(function (jqXHR, textStatus, errorThrown) {
				that._error((jqXHR.responseJSON || {}).error);
			});
		},

		/**
		 * @protected
		 *
		 * @param {jQuery}  $template
		 * @param {object}  [vars]
		 * @param {boolean} [replaceContent]
		 *
		 * @returns {jQuery}
		 */
		_content: function ($template, vars, replaceContent) {
			var $baseElement = $($template.data('replaces') || $template.data('appendTo') || this.$element),
				$content = $template.octemplate(vars || {});

			$baseElement.find('.has-tooltip').tooltip('hide');
			$content.find('.has-tooltip').tooltip();

			if ((replaceContent !== undefined) ? replaceContent : $template.data('replaces')) {
				$baseElement.empty();
			}

			$content.appendTo($baseElement);

			return $content;
		},

		/**
		 * @protected
		 *
		 * @param {string} [message]
		 */
		_error: function (message) {
			var $error = this._content(this.$errorTemplate, { message: message || '' }),
				that = this;

			if (message) {
				$error.find('.error-details').show();
			}

			$error.find('.action-reload').on('click.CMSPicoAdminList', function (event) {
				event.preventDefault();
				that.reload();
			});
		}
	};

	/**
	 * @class
	 *
	 * @param {jQuery} $element
	 * @param {Object} [options]
	 * @param {string} [options.route]
	 */
	OCA.CMSPico.Form = function ($element, options) {
		this.initialize($element, options);
	};

	/**
	 * @lends OCA.CMSPico.Form.prototype
	 */
	OCA.CMSPico.Form.prototype = {
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

			var signature = 'OCA.CMSPico.Form.initialize()';
			if (!this.route) throw signature + ': No route given';
		},

		/**
		 * @public
		 * @abstract
		 */
		prepare: function () {},

		/**
		 * @public
		 * @abstract
		 */
		submit: function () {},

		/**
		 * @protected
		 *
		 * @param {jQuery}              $element
		 * @param {string|number|Array} [value]
		 *
		 * @returns {jQuery|string|number|Array}
		 */
		_val: function ($element, value) {
			if (value !== undefined) {
				return $element.is(':input') ? $element.val(value) : $element.text(value);
			}

			return $element.is(':input') ? $element.val() : $element.text();
		}
	};

	/**
	 * @class
	 *
	 * @param {jQuery}   $template
	 * @param {object}   options
	 * @param {string}   options.title
	 * @param {object}   [options.templateData]
	 * @param {object[]} [options.buttons]
	 */
	OCA.CMSPico.Dialog = function ($template, options) {
		this.initialize($template, options);
	};

	/**
	 * @lends OCA.CMSPico.Dialog
	 */
	$.extend(OCA.CMSPico.Dialog, {
		/**
		 * @type {number}
		 * @constant
		 */
		BUTTON_ABORT: 1,

		/**
		 * @type {number}
		 * @constant
		 */
		BUTTON_SUBMIT: 2,

		/**
		 * @type {number}
		 * @protected
		 */
		dialogId: 0
	});

	/**
	 * @lends OCA.CMSPico.Dialog.prototype
	 */
	OCA.CMSPico.Dialog.prototype = {
		/** @member {jQuery} */
		$element: $(),

		/** @member {string} */
		dialogId: '',

		/** @member {jQuery} */
		$template: $(),

		/** @member {string} */
		title: '',

		/** @member {object} */
		templateData: {},

		/** @member {object[]} */
		buttons: [],

		/** @member {object} */
		events: {},

		/** @member {boolean} */
		opened: false,

		/**
		 * @constructs
		 *
		 * @param {jQuery}   $template
		 * @param {object}   options
		 * @param {string}   options.title
		 * @param {object}   [options.templateData]
		 * @param {object[]} [options.buttons]
		 */
		initialize: function ($template, options) {
			this.$template = $template;

			options = $.extend({
				title: '',
				templateData: {},
				buttons: []
			}, options);

			this.title = options.title;
			this.templateData = options.templateData;
			this.buttons = options.buttons;
			this.events = {};

			this.dialogId = 'picocms-dialog-' + ++OCA.CMSPico.Dialog.dialogId;

			var signature = 'OCA.CMSPico.Dialog.initialize()';
			if (!this.title) throw signature + ': No dialog title given';
		},

		/**
		 * @protected
		 *
		 * @returns {object[]}
		 */
		_getButtons: function () {
			var buttons = [],
				that = this;

			for (var i = 0; i < this.buttons.length; i++) {
				if (this.buttons[i].type !== undefined) {
					switch (this.buttons[i].type) {
						case OCA.CMSPico.Dialog.BUTTON_ABORT:
							buttons.push($.extend({
								text: t('cms_pico', 'Abort'),
								click: function (event) {
									that.close();
								}
							}, this.buttons[i]));
							break;

						case OCA.CMSPico.Dialog.BUTTON_SUBMIT:
							buttons.push($.extend({
								text: t('cms_pico', 'Save'),
								defaultButton: true,
								click: function (event) {
									that.submit();
									that.close();
								}
							}, this.buttons[i]));
							break;
					}
				} else {
					buttons.push(this.buttons[i]);
				}
			}

			return buttons;
		},

		/**
		 * @public
		 */
		open: function () {
			if (this.opened) {
				// nothing to do
				return;
			}

			this.$element = this.$template.octemplate($.extend({}, this.templateData, {
				id: this.dialogId,
				title: this.title
			}));

			$('#app-content').append(this.$element);

			var that = this;
			this.$element.ocdialog({
				buttons: this._getButtons(),
				close: function () {
					that.opened = false;
					that.close();
				}
			});

			this._trigger('open');
			this.opened = true;
		},

		/**
		 * @public
		 */
		submit: function () {
			this._trigger('submit');
		},

		/**
		 * @public
		 */
		close: function () {
			if (this.opened) {
				this.$element.ocdialog('close');
				return;
			}

			this._trigger('close');
		},

		/**
		 * @public
		 *
		 * @param {string}   eventName
		 * @param {function} callback
		 */
		on: function (eventName, callback) {
			var event = this._parseEventName(eventName);
			if (event === false) {
				$.error('Invalid event name: ' + eventName);
				return;
			}

			if (this.events[event[0]] === undefined) {
				this.events[event[0]] = {};
			}

			this.events[event[0]][event[1]] = callback;
		},

		/**
		 * @public
		 *
		 * @param {string} eventName
		 */
		off: function (eventName) {
			var event = this._parseEventName(eventName);
			if (event === false) {
				$.error('Invalid event name: ' + eventName);
				return false;
			}

			if ((this.events[event[0]] !== undefined) && (this.events[event[0]][event[1]] !== undefined)) {
				delete this.events[event[0]][event[1]];
				return true;
			}

			return false;
		},

		/**
		 * @protected
		 *
		 * @param {string} eventType
		 */
		_trigger: function (eventType) {
			if (!this.events[eventType]) {
				return;
			}

			var that = this;
			$.each(this.events[eventType], function (id, callback) {
				callback.apply(that);
			});
		},

		/**
		 * @protected
		 *
		 * @param {string} eventName
		 *
		 * @returns {[string, string]|false}
		 */
		_parseEventName: function (eventName) {
			var pos = eventName.indexOf('.');
			pos = (pos !== -1) ? pos : eventName.length;

			var type = eventName.substr(0, pos),
				id = eventName.substr(pos + 1);

			if (!type || !id) {
				return false;
			}

			return [ type, id ];
		}
	};

	/** @namespace OCA.CMSPico.Util */
	OCA.CMSPico.Util = {
		/**
		 * @param {string} string
		 *
		 * @returns string
		 */
		unescape: function (string) {
			return string
				.replace(/&amp;/g, '&')
				.replace(/&lt;/g, '<')
				.replace(/&gt;/g, '>')
				.replace(/&quot;/g, '"')
				.replace(/&#039;/g, "'");
		}
	};
})(document, jQuery, OC, OCA);
