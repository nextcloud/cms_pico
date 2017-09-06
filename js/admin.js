/** global: OC */

var elements = {
	test_cms_pico: null
};

$(document).ready(function () {

	elements.test_cms_pico = $('#test_cms_pico');
	elements.test_cms_pico.on('change', function () {
		saveChange();
	});

	saveChange = function () {
		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/cms_pico/admin/settings'),
			data: {
				data: {
					test_admin: (elements.test_cms_pico.is(':checked')) ? '1' : '0'
				}
			}
		}).done(function (res) {
			self.refreshSettings(res);
		});
	};

	refreshSettings = function (result) {
		elements.test_cms_pico.prop('checked', (result.test_admin === '1'));
	};

	$.ajax({
		method: 'GET',
		url: OC.generateUrl('/apps/cms_pico/admin/settings'),
		data: {}
	}).done(function (res) {
		self.refreshSettings(res);
	});

});