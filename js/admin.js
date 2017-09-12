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
/** global: admin_pico_elements */
/** global: admin_pico_result */
/** global: admin_pico_nav */

var admin_pico_define = {
	sites: '/sites/',
	index: '/index.php',
	nchost: ''
};


$(document).ready(function () {

	/**
	 * @constructs AdminCMSPico
	 */
	var AdminCMSPico = function () {

		$.extend(AdminCMSPico.prototype, admin_pico_nav);
		$.extend(AdminCMSPico.prototype, admin_pico_elements);
		$.extend(AdminCMSPico.prototype, admin_pico_result);
		$.extend(AdminCMSPico.prototype, admin_pico_define);

		this.initialize();
	};


	AdminCMSPico.prototype = {

		initialize: function () {
			admin_pico_define.nchost = window.location.protocol + '//' + window.location.host;
			admin_pico_elements.initElements();
			admin_pico_elements.initUI();

			admin_pico_nav.retrieveSettings();
		}

	};

	OCA.AdminCMSPico = AdminCMSPico;
	OCA.AdminCMSPico.manage = new AdminCMSPico();

});
