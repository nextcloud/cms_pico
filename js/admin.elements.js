/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2017, Maxence Lange (<maxence@artificial-owl.com>)
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

/** global: admin_pico_nav */

var admin_pico_elements = {

	cms_pico_curr_templates: null,
	cms_pico_new_template: null,
	cms_pico_curr_themes: null,
	cms_pico_new_theme: null,
	cms_pico_refresh: null,
	cms_pico_submit_template: null,
	cms_pico_submit_theme: null,

	init: function() {
		admin_pico_elements.initElements();
		admin_pico_elements.initUI();
		admin_pico_elements.initTweaks();
	},


	initElements: function () {
		admin_pico_elements.cms_pico_curr_templates = $('#admin_cms_pico_curr_templates');
		admin_pico_elements.cms_pico_new_template = $('#admin_cms_pico_new_templates');
		admin_pico_elements.cms_pico_curr_themes = $('#admin_cms_pico_curr_themes');
		admin_pico_elements.cms_pico_new_theme = $('#admin_cms_pico_new_themes');
		admin_pico_elements.cms_pico_refresh = $('.admin_cms_pico_refresh');
		admin_pico_elements.cms_pico_submit_template = $('#admin_cms_pico_add_template_submit');
		admin_pico_elements.cms_pico_submit_theme = $('#admin_cms_pico_add_theme_submit');
	},


	initUI: function () {
		admin_pico_elements.cms_pico_refresh.css('cursor', 'pointer').on('click', function () {
			admin_pico_nav.retrieveSettings();
		});

		admin_pico_elements.cms_pico_submit_template.on('click', function() {
			admin_pico_nav.addCustomTemplate();
		});

		admin_pico_elements.cms_pico_submit_theme.on('click', function() {
			admin_pico_nav.addCustomTheme();
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