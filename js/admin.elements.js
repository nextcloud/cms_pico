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


var admin_pico_elements = {

	cms_pico_new_templates: null,


	initElements: function () {
		admin_pico_elements.cms_pico_new_templates = $('#cms_pico_new_templates');
	},


	initUI: function () {
		// pico_elements.cms_pico_new_website.on('input propertychange paste focus', function () {
		// 	pico_nav.updateNewWebsite($(this).val());
		// });
		//
		// pico_elements.cms_pico_new_folder.on('click', function () {
		// 	OC.dialogs.filepicker(t('cms_pico', 'test'), pico_nav.pickFolderResult, false,
		// 		"httpd/unix-directory", true);
		// });
		//
		// pico_elements.cms_pico_new_submit.on('click', function () {
		// 	pico_nav.createNewWebsite();
		// });
	}

};