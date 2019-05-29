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

/** global: OC */
/** global: pico_nav */
/** global: pico_result */

var pico_elements = {

	cms_pico_list_websites: null,
	cms_pico_new_name: null,
	cms_pico_new_url: null,
	cms_pico_new_website: null,
	cms_pico_new_template: null,
	cms_pico_new_folder: null,
	cms_pico_new_folder_result: '',
	cms_pico_new_submit: null,


	init: function () {
		pico_elements.initElements();
		pico_elements.initUI();
		pico_elements.initTweaks();
	},

	initElements: function () {

		pico_elements.cms_pico_list_websites = $('#cms_pico_list_websites');
		pico_elements.cms_pico_new_name = $('#cms_pico_new_name');
		pico_elements.cms_pico_new_website = $('#cms_pico_new_website');
		pico_elements.cms_pico_new_url = $('#cms_pico_new_url');
		pico_elements.cms_pico_new_template = $('#cms_pico_new_template');
		pico_elements.cms_pico_new_folder = $('#cms_pico_new_folder');
		pico_elements.cms_pico_new_submit = $('#cms_pico_new_submit');
	},


	initUI: function () {
		pico_elements.cms_pico_new_website.on('input propertychange paste focus', function () {
			pico_nav.updateNewWebsite($(this).val());
		});

		pico_elements.cms_pico_new_folder.on('click', function () {
			OC.dialogs.filepicker(t('cms_pico', 'test'), pico_nav.pickFolderResult, false,
				"httpd/unix-directory", true);
		});

		pico_elements.cms_pico_new_submit.on('click', function () {
			pico_nav.createNewWebsite();
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
