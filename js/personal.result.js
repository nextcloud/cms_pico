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

			pico_elements.cms_pico_list_websites.children('tr').each(function () {
				pico_result.interactionWebsitesDelete($(this));
				pico_result.displayWebsitesLink($(this));
				pico_result.displayWebsitesPath($(this));
				pico_result.displayWebsitesPrivate($(this));
			});

		},


		displayWebsitesLink: function (div) {
			var url = div.attr('data-address');
			div.find('TD.link').css('cursor', 'pointer').on('click', function () {
				window.open(url);
			});
		},


		displayWebsitesPath: function (div) {
			var url = pico_define.nchost + pico_define.index + OC.appswebroots.files + '/?dir=' +
				div.attr('data-path');
			div.find('TD.path').css('cursor', 'pointer').on('click', function () {
				window.open(url);
			});
		},


		displayWebsitesPrivate: function (div) {
			div.find('INPUT.private').prop('checked', div.attr('data-private') === '1').on(
				'change', function () {
					pico_nav.updateWebsiteOption(div.attr('data-id'), 'private',
						($(this).is(':checked')) ? '1' : '0');
				});
		},


		interactionWebsitesDelete: function (div) {
			div.find('BUTTON.icon-delete').on('click', function () {
				pico_nav.deleteWebsite(div.attr('data-id'), div.attr('data-name'));
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
		},


		deleteWebsiteResult: function (result) {

			if (result.status === 1) {
				OCA.notification.onSuccess('Website removed');
				pico_result.displayWebsites(result.websites);
				return;
			}

			OCA.notification.onFail(
				t('cms_pico', "It was not possible to remove the website {name}",
					{name: result.name}) +
				': ' + ((result.error) ? result.error : t('circles', 'no error message')));
		}

	}
;