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
/** global: OCA */
/** global: pico_elements */
/** global: pico_result */
/** global: pico_define */

var pico_nav = {


	retrieveWebsites: function () {
		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/cms_pico/personal/websites'),
			data: {}
		}).done(function (res) {
			pico_nav.retrieveWebsitesResult(res);
		});
	},


	retrieveWebsitesResult: function (result) {
		if (result.status === 1) {
			pico_define.themes = result.themes;
			pico_result.displayWebsites(result.websites);
			return;
		}

		OCA.notification.onFail(
			t('cms_pico', "It was not possible to retrieve the complete list of your websites") +
			': ' + ((result.error) ? result.error : t('cms_pico', 'no error message')));
	},


	updateNewWebsite: function (url) {
		pico_elements.cms_pico_new_url.text(pico_define.nchost + pico_define.sites + url);
		pico_nav.refreshNewFolder();
	},


	updateTheme: function (site_id, theme) {
		$.ajax({
			method: 'PUT',
			url: OC.generateUrl('/apps/cms_pico/personal/website/' + site_id + '/theme'),
			data: {
				theme: theme
			}
		}).done(function (res) {
			pico_nav.updateThemeResult(res);
		});
	},


	updateThemeResult: function (result) {
		if (result.status === 1) {
			OCA.notification.onSuccess('Theme updated');
			pico_result.displayWebsites(result.websites);
			return;
		}

		OCA.notification.onFail(
			t('cms_pico', "It was not possible to update your theme on {name}",
				{name: result.name}) +
			': ' + ((result.error) ? result.error : t('cms_pico', 'no error message')));
	},


	pickFolderResult: function (folder) {
		pico_elements.cms_pico_new_folder_result = folder;
		pico_nav.refreshNewFolder();
	},


	refreshNewFolder: function () {
		pico_elements.cms_pico_new_folder.val(pico_elements.cms_pico_new_folder_result + '/' +
			pico_elements.cms_pico_new_website.val());
	},


	createNewWebsite: function () {

		pico_nav.creatingWebsite(true);
		var data = {
			name: pico_elements.cms_pico_new_name.val(),
			website: pico_elements.cms_pico_new_website.val(),
			path: pico_elements.cms_pico_new_folder.val(),
			template: pico_elements.cms_pico_new_template.val()
		};

		$.ajax({
			method: 'PUT',
			url: OC.generateUrl('/apps/cms_pico/personal/website'),
			data: {
				data: data
			}
		}).done(function (result) {
			pico_nav.creatingWebsite(false);
			pico_result.createNewWebsiteResult(result);
		});

	},


	deleteWebsite: function (id, name) {

		OC.dialogs.confirm(
			t('cms_pico',
				"This operation will delete the website {name} but its content will still be available in your files",
				{name: name}),
			t('cms_pico', 'Please confirm'),
			function (e) {
				if (e === true) {
					pico_nav.forceDeleteWebsite(id, name);
				}
			});
	},


	forceDeleteWebsite: function (id, name) {
		$.ajax({
			method: 'DELETE',
			url: OC.generateUrl('/apps/cms_pico/personal/website'),
			data: {
				data: {
					id: id,
					name: name
				}
			}
		}).done(function (result) {
			pico_result.deleteWebsiteResult(result);
		});
	},


	creatingWebsite: function (creating) {

		pico_elements.cms_pico_new_submit.prop('disabled', creating);
		if (creating) {
			pico_elements.cms_pico_new_submit.val(t('cms_pico', 'Please wait'));
		} else {
			pico_elements.cms_pico_new_submit.val(t('cms_pico', 'Create a new website'));
		}

	},


	updateWebsiteOption: function (site_id, key, value) {
		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/cms_pico/personal/website/' + site_id + '/option/' + key),
			data: {
				value: value
			}
		}).done(function (res) {
			pico_nav.updateWebsiteOptionResult(res);
		});
	},


	updateWebsiteOptionResult: function (result) {
		if (result.status === 1) {
			OCA.notification.onSuccess('Option updated');
			pico_result.displayWebsites(result.websites);
			return;
		}

		OCA.notification.onFail(
			t('cms_pico', "It was not possible to update your website") +
			': ' + ((result.error) ? result.error : t('cms_pico', 'no error message')));
	},


	resetFields: function () {
		pico_elements.cms_pico_new_website.val('');
		pico_elements.cms_pico_new_name.val('');
		pico_elements.cms_pico_new_folder.val('/');
		pico_elements.cms_pico_new_url.text('');
		pico_elements.cms_pico_new_folder_result = '';
	},


	generateTmplWebsite: function (entry) {
		var div = $('#tmpl_website');
		if (!div.length) {
			return;
		}

		var tmpl = div.html();

		tmpl = tmpl.replace(/%%id%%/g, entry.id);
		tmpl = tmpl.replace(/%%name%%/g, escapeHTML(entry.name));
		tmpl = tmpl.replace(/%%address%%/g, pico_define.nchost + pico_define.sites +
			escapeHTML(entry.site));
		tmpl = tmpl.replace(/%%path%%/g, escapeHTML(entry.path));
		tmpl = tmpl.replace(/%%theme%%/g, escapeHTML(entry.theme));

		if (entry.options.private === '1') {
			tmpl = tmpl.replace(/%%private%%/g, escapeHTML(entry.options.private));
		}

		return tmpl;
	}


};