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

var elements = {
	cms_pico_list_websites: null,
	cms_pico_new_name: null,
	cms_pico_new_url: null,
	cms_pico_new_website: null,
	cms_pico_new_path: null,
	cms_pico_new_folder: null,
	cms_pico_new_folder_result: '',
	cms_pico_new_submit: null
};

var define = {
	sites: '/sites/',
	index: '/index.php',
	nchost: ''
};

$(document).ready(function () {

	elements.cms_pico_list_websites = $('#cms_pico_list_websites');
	elements.cms_pico_new_name = $('#cms_pico_new_name');
	elements.cms_pico_new_website = $('#cms_pico_new_website');
	elements.cms_pico_new_url = $('#cms_pico_new_url');
	elements.cms_pico_new_path = $('#cms_pico_new_path');
	elements.cms_pico_new_folder = $('#cms_pico_new_folder');
	elements.cms_pico_new_submit = $('#cms_pico_new_submit');

	elements.cms_pico_new_website.on('input propertychange paste focus', function () {
		updateNewWebsite($(this).val())
	});

	elements.cms_pico_new_folder.on('click', function () {
		OC.dialogs.filepicker(t('cms_pico', 'test'), self.pickFolderResult, false,
			"httpd/unix-directory", true);
	});

	elements.cms_pico_new_submit.on('click', function () {
		createNewWebsite();
	});

	updateNewWebsite = function (url) {
		elements.cms_pico_new_url.text(define.nchost + define.sites + url);
		refreshNewFolder();
	};

	pickFolderResult = function (folder) {
		elements.cms_pico_new_folder_result = folder;
		refreshNewFolder();
	};

	refreshNewFolder = function () {
		elements.cms_pico_new_path.text(elements.cms_pico_new_folder_result + '/' +
			elements.cms_pico_new_website.val());
	};

	createNewWebsite = function () {

		var data = {
			name: elements.cms_pico_new_name.val(),
			website: elements.cms_pico_new_website.val(),
			path: elements.cms_pico_new_path.text()
		};

		$.ajax({
			method: 'PUT',
			url: OC.generateUrl('/apps/cms_pico/personal/website'),
			data: {
				data: data
			}
		}).done(function (result) {
			if (result.status === 1) {
				OCA.notification.onSuccess('Website created');
				displayWebsites(result.websites);
				return;
			}
			OCA.notification.onFail(
				t('cms_pico', "It was not possible to create your website {name}",
					{name: result.name}) +
				': ' + ((result.error) ? result.error : t('circles', 'no error message')));
		});

	};

	displayWebsites = function (list) {

		elements.cms_pico_list_websites.emptyTable();

		for (var i = 0; i < list.length; i++) {
			var tmpl = self.generateTmplWebsite(list[i]);
			elements.cms_pico_list_websites.append(tmpl);
		}

		displayWebsitesLink();
		displayWebsitesPath();
		displayWebsitesPrivate();
	};


	displayWebsitesLink = function () {
		elements.cms_pico_list_websites.find('TD.link').each(function () {
			var url = $(this).parent().attr('data-address');
			$(this).css('cursor', 'pointer').on('click', function () {
				window.open(url);
			});
		});
	};


	displayWebsitesPath = function () {
		elements.cms_pico_list_websites.find('TD.path').each(function () {
			var url = define.nchost + define.index + OC.appswebroots.files + '/?dir=' +
				$(this).parent().attr('data-path');
			$(this).css('cursor', 'pointer').on('click', function () {
				window.open(url);
			});
		});
	};


	displayWebsitesPrivate = function () {
		elements.cms_pico_list_websites.find('INPUT.private').each(function () {
			$(this).prop('checked', ($(this).parent().parent().attr('data-private') === '1'));
			$(this).on(
				'change', function () {
					updateWebsiteOption($(this).parent().parent().attr('data-id'), 'private',
						($(this).is(':checked')) ? '1' : '0');
				});
		});
	};

	updateWebsiteOption = function (site_id, key, value) {
		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/cms_pico/personal/website/' + site_id + '/option/' + key),
			data: {
				value: value
			}
		}).done(function (res) {
			self.displayWebsites(res.websites);
		});
	};


	generateTmplWebsite = function (entry) {
		var tmpl = $('#tmpl_website').html();

		tmpl = tmpl.replace(/%%id%%/g, entry.id);
		tmpl = tmpl.replace(/%%name%%/g, escapeHTML(entry.name));
		tmpl = tmpl.replace(/%%address%%/g, define.nchost + define.sites + escapeHTML(entry.site));
		tmpl = tmpl.replace(/%%path%%/g, escapeHTML(entry.path));

		if (entry.options.private === '1') {
			tmpl = tmpl.replace(/%%private%%/g, escapeHTML(entry.options.private));
		}

		return tmpl;
	};


	$.ajax({
		method: 'GET',
		url: OC.generateUrl('/apps/cms_pico/personal/websites'),
		data: {}
	}).done(function (res) {
		self.displayWebsites(res.websites);
	});

	define.nchost = window.location.protocol + '//' + window.location.host;

	initTweaks = function () {
		$.fn.emptyTable = function () {
			this.children('tr').each(function () {
				if ($(this).attr('class') !== 'header') {
					$(this).remove();
				}
			});
		};
	};

	self.initTweaks();
});


/**
 * @constructs Notification
 */
var Notification = function () {
	this.initialize();
};

Notification.prototype = {

	initialize: function () {

		var notyf = new Notyf({
			delay: 5000
		});

		this.onSuccess = function (text) {
			notyf.confirm(text);
		};

		this.onFail = function (text) {
			notyf.alert(text);
		};

	}

};

OCA.Notification = Notification;
OCA.notification = new Notification();
