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

/** global: pico_elements */
/** global: pico_define */
/** global: pico_nav */

var pico_nav = {

	updateNewWebsite: function (url) {
		pico_elements.cms_pico_new_url.text(define.nchost + define.sites + url);
		pico_nav.refreshNewFolder();
	},


	pickFolderResult: function (folder) {
		pico_elements.cms_pico_new_folder_result = folder;
		pico_nav.refreshNewFolder();
	},


	refreshNewFolder: function () {
		pico_elements.cms_pico_new_path.text(pico_elements.cms_pico_new_folder_result + '/' +
			pico_elements.cms_pico_new_website.val());
	},


	createNewWebsite: function () {

		var data = {
			name: pico_elements.cms_pico_new_name.val(),
			website: pico_elements.cms_pico_new_website.val(),
			path: pico_elements.cms_pico_new_path.text()
		};

		$.ajax({
			method: 'PUT',
			url: OC.generateUrl('/apps/cms_pico/personal/website'),
			data: {
				data: data
			}
		}).done(function (result) {
			pico_result.createNewWebsiteResult(result);
		});

	},


	updateWebsiteOption: function (site_id, key, value) {
		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/cms_pico/personal/website/' + site_id + '/option/' + key),
			data: {
				value: value
			}
		}).done(function (res) {
			pico_result.displayWebsites(res.websites);
		});
	},


	generateTmplWebsite: function (entry) {
		var tmpl = $('#tmpl_website').html();

		tmpl = tmpl.replace(/%%id%%/g, entry.id);
		tmpl = tmpl.replace(/%%name%%/g, escapeHTML(entry.name));
		tmpl = tmpl.replace(/%%address%%/g, pico_define.nchost + pico_define.sites + escapeHTML(entry.site));
		tmpl = tmpl.replace(/%%path%%/g, escapeHTML(entry.path));

		if (entry.options.private === '1') {
			tmpl = tmpl.replace(/%%private%%/g, escapeHTML(entry.options.private));
		}

		return tmpl;
	}


};