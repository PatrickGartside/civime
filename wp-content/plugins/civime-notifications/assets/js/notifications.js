/* CiviMe Notifications — Plugin Scripts */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // 1. Channel toggle — show/hide email and phone fields based on checkboxes
        var channelCheckboxes = document.querySelectorAll('[data-toggle-target]');

        channelCheckboxes.forEach(function (checkbox) {
            var targetId = checkbox.getAttribute('data-toggle-target');
            var target = document.getElementById(targetId);

            if (!target) return;

            var update = function () {
                if (checkbox.checked) {
                    target.removeAttribute('hidden');
                } else {
                    target.setAttribute('hidden', '');
                }
            };

            checkbox.addEventListener('change', update);
        });

        // 2. Council picker search — filter checkboxes by name
        var pickers = document.querySelectorAll('.council-picker');

        pickers.forEach(function (picker) {
            var search = picker.querySelector('.council-picker__search');
            var items = picker.querySelectorAll('.council-picker__item');
            var countEl = picker.querySelector('.council-picker__count');

            if (!search || !items.length) return;

            search.addEventListener('input', function () {
                var query = search.value.toLowerCase().trim();
                var visible = 0;

                items.forEach(function (item) {
                    var name = item.getAttribute('data-council-name') || '';

                    if (query === '' || name.indexOf(query) !== -1) {
                        item.removeAttribute('hidden');
                        visible++;
                    } else {
                        item.setAttribute('hidden', '');
                    }
                });

                if (countEl) {
                    if (query === '') {
                        countEl.textContent = '';
                    } else {
                        countEl.textContent = visible + ' council' + (visible !== 1 ? 's' : '') + ' found';
                    }
                }
            });

            // Update selected count on change
            var updateSelected = function () {
                var checked = picker.querySelectorAll('.council-picker__checkbox:checked');

                if (countEl && search.value.trim() === '') {
                    if (checked.length > 0) {
                        countEl.textContent = checked.length + ' selected';
                    } else {
                        countEl.textContent = '';
                    }
                }
            };

            items.forEach(function (item) {
                var cb = item.querySelector('.council-picker__checkbox');
                if (cb) {
                    cb.addEventListener('change', updateSelected);
                }
            });

            // Initial count
            updateSelected();
        });

        // 3. Focus first error on page load if errors are present
        var errorNotice = document.querySelector('.notif-notice--error');
        if (errorNotice) {
            errorNotice.scrollIntoView({ behavior: 'smooth', block: 'start' });
            errorNotice.focus();
        }
    });
})();
