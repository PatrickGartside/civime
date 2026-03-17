/**
 * CiviMe I18n — Language switcher auto-submit.
 *
 * Replaces the inline onchange handler (blocked by CSP) with an external
 * event listener that submits the form when the user selects a language.
 *
 * @package CiviMe_I18n
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var selects = document.querySelectorAll('.lang-switcher__select');

		selects.forEach(function (select) {
			select.addEventListener('change', function () {
				this.form.submit();
			});
		});
	});
})();
