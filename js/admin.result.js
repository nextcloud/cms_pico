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
/** global: admin_pico_define */
/** global: admin_pico_elements */
/** global: admin_pico_nav */

var admin_pico_result = {

	displaySettings: function (settings) {
		admin_pico_result.displayNewTemplates(settings.templates_new);
		admin_pico_result.displayCurrentTemplates(settings.templates);
	},


	displayNewTemplates: function (templates) {
		admin_pico_elements.cms_pico_new_template.empty();
		for (var i = 0; i < templates.length; i++) {
			admin_pico_elements.cms_pico_new_template.append($('<option>', {
				value: templates[i],
				text: templates[i]
			}));
		}
	},


	displayCurrentTemplates: function (templates) {
		admin_pico_elements.cms_pico_curr_templates.emptyTable();
		for (var i = 0; i < templates.length; i++) {
			var tmpl = admin_pico_nav.generateTmplCustomTemplate(templates[i]);
			admin_pico_elements.cms_pico_curr_templates.append(tmpl);
		}

		admin_pico_elements.cms_pico_curr_templates.children('tr').each(function () {
			admin_pico_nav.interactionCurrentTemplate($(this));
		});
	}


};