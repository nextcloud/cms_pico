var elements = {
	cms_pico_list_websites: null,
	cms_pico_new_url: null,
	cms_pico_new_website: null,
	cms_pico_new_path: null,
	cms_pico_new_folder: null,
	cms_pico_new_folder_result: '',
	cms_pico_new_submit: null
};

$(document).ready(function () {

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
		elements.cms_pico_new_url.text('http://www.nextcloud.com/sites/' + url);
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
			website: elements.cms_pico_new_website.val(),
			path: elements.cms_pico_new_path.text()
		};

		$.ajax({
			method: 'PUT',
			url: OC.generateUrl('/apps/cms_pico/personal/website'),
			data: {
				data: data
			}
		}).done(function (res) {
			console.log(res);
		});

	};


	$.ajax({
		method: 'GET',
		url: OC.generateUrl('/apps/cms_pico/personal/websites'),
		data: {}
	}).done(function (res) {
		console.log(JSON.stringify(res));
	});

});