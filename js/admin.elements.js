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

/** global: admin_pico_nav */

var admin_pico_elements = {

	cms_pico_curr_templates: null,
	cms_pico_new_template: null,
	cms_pico_refresh_templates: null,
	cms_pico_submit: null,

	init: function() {
		admin_pico_elements.initElements();
		admin_pico_elements.initUI();
		admin_pico_elements.initTweaks();
	},


	initElements: function () {
		admin_pico_elements.cms_pico_curr_templates = $('#admin_cms_pico_curr_templates');
		admin_pico_elements.cms_pico_new_template = $('#admin_cms_pico_new_templates');
		admin_pico_elements.cms_pico_refresh_templates = $('#admin_cms_pico_refresh_templates');
		admin_pico_elements.cms_pico_submit = $('#admin_cms_pico_add_submit');
	},


	initUI: function () {
		admin_pico_elements.cms_pico_refresh_templates.css('cursor', 'pointer').on('click', function () {
			admin_pico_nav.retrieveSettings();
		});

		admin_pico_elements.cms_pico_submit.on('click', function() {
			admin_pico_nav.addCustomTemplate();
		});
	},

	
	initTweaks: function () {
		$.fn.emptyTable = function () {
			this.children('tr').each(function () {
				if ($(this).attr('class') !== 'header') {
					$(this).remove();
				}
			});
		};
	}
};