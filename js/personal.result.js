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

/** global: pico_define */
/** global: pico_elements */
/** global: pico_nav */

var pico_result = {

	displayWebsites: function (list) {

		pico_elements.cms_pico_list_websites.emptyTable();

		for (var i = 0; i < list.length; i++) {
			var tmpl = pico_nav.generateTmplWebsite(list[i]);
			pico_elements.cms_pico_list_websites.append(tmpl);
		}

		pico_result.displayWebsitesLink();
		pico_result.displayWebsitesPath();
		pico_result.displayWebsitesPrivate();
	},


	displayWebsitesLink: function () {
		pico_elements.cms_pico_list_websites.find('TD.link').each(function () {
			var url = $(this).parent().attr('data-address');
			$(this).css('cursor', 'pointer').on('click', function () {
				window.open(url);
			});
		});
	},


	displayWebsitesPath: function () {
		pico_elements.cms_pico_list_websites.find('TD.path').each(function () {
			var url = pico_define.nchost + pico_define.index + OC.appswebroots.files + '/?dir=' +
				$(this).parent().attr('data-path');
			$(this).css('cursor', 'pointer').on('click', function () {
				window.open(url);
			});
		});
	},


	displayWebsitesPrivate: function () {
		pico_elements.cms_pico_list_websites.find('INPUT.private').each(function () {
			$(this).prop('checked', ($(this).parent().parent().attr('data-private') === '1'));
			$(this).on(
				'change', function () {
					pico_nav.updateWebsiteOption($(this).parent().parent().attr('data-id'), 'private',
						($(this).is(':checked')) ? '1' : '0');
				});
		});
	},


	createNewWebsiteResult: function (result) {

		if (result.status === 1) {
			OCA.notification.onSuccess('Website created');
			pico_result.displayWebsites(result.websites);
			pico_nav.resetFields();
			return;
		}

		OCA.notification.onFail(
			t('cms_pico', "It was not possible to create your website {name}",
				{name: result.name}) +
			': ' + ((result.error) ? result.error : t('circles', 'no error message')));
	}

};