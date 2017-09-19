/*
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */


/** global: OC */
/** global: OCA */
/** global: oc_config */
/** global: Notyf */
/** global: pico_elements */
/** global: pico_nav */
/** global: pico_result */

var pico_define = {
	sites: '/sites/',
	index: '',
	nchost: '',

	init: function () {
		pico_define.nchost = window.location.protocol + '//' + window.location.host;

		pico_define.index = OC.getRootPath();
		if (oc_config.modRewriteWorking !== true) {
			pico_define.index += '/index.php';
		}
	}
};


$(document).ready(function () {

	/**
	 * @constructs CMSPico
	 */
	var CMSPico = function () {

		$.extend(CMSPico.prototype, pico_nav);
		$.extend(CMSPico.prototype, pico_elements);
		$.extend(CMSPico.prototype, pico_result);

		pico_define.init();
		pico_elements.init();

		pico_nav.retrieveWebsites();
	};

	/**
	 * @constructs Notification
	 */
	var Notification = function () {
		this.initialize();
	};

	Notification.prototype = {

		initialize: function () {

			var notyf = new Notyf({
				delay: 5000
			});

			this.onSuccess = function (text) {
				notyf.confirm(text);
			};

			this.onFail = function (text) {
				notyf.alert(text);
			};

		}

	};

	OCA.CMSPico = CMSPico;
	OCA.CMSPico.manage = new CMSPico();

	OCA.Notification = Notification;
	OCA.notification = new Notification();

});
