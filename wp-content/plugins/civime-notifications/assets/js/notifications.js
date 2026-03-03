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

        // 2. Council picker search + topic filter — filter checkboxes by name and topic
        var pickers = document.querySelectorAll('.council-picker');

        pickers.forEach(function (picker) {
            var search = picker.querySelector('.council-picker__search');
            var items = picker.querySelectorAll('.council-picker__item');
            var countEl = picker.querySelector('.council-picker__count');

            if (!search || !items.length) return;

            // Topic chip state — shared between search and topic filters.
            var activeTopics = [];

            // Find topic picker (sibling before the council-picker in the fieldset).
            var fieldset = picker.closest('.subscribe-form__group');
            var topicPicker = fieldset ? fieldset.querySelector('.meetings-topic-picker') : null;
            var topicChips = topicPicker ? topicPicker.querySelectorAll('[data-topic-slug]') : [];
            var topicStatus = topicPicker ? topicPicker.querySelector('.meetings-topic-picker__status') : null;
            var topicCountEl = topicPicker ? topicPicker.querySelector('.meetings-topic-picker__count') : null;
            var topicClearBtn = topicPicker ? topicPicker.querySelector('.meetings-topic-picker__clear') : null;

            /**
             * Apply both search and topic filters simultaneously.
             * An item is shown only if it passes both filters.
             */
            var applyFilters = function () {
                var query = search.value.toLowerCase().trim();
                var visible = 0;
                var hasTopicFilter = activeTopics.length > 0;
                var hasSearchFilter = query !== '';

                items.forEach(function (item) {
                    var name = item.getAttribute('data-council-name') || '';
                    var itemTopics = (item.getAttribute('data-council-topics') || '').split(',').filter(Boolean);

                    var matchesSearch = !hasSearchFilter || name.indexOf(query) !== -1;
                    var matchesTopic = !hasTopicFilter || activeTopics.some(function (slug) {
                        return itemTopics.indexOf(slug) !== -1;
                    });

                    if (matchesSearch && matchesTopic) {
                        item.removeAttribute('hidden');
                        visible++;
                    } else {
                        item.setAttribute('hidden', '');
                    }
                });

                // Update count text.
                if (countEl) {
                    if (hasSearchFilter || hasTopicFilter) {
                        countEl.textContent = visible + ' council' + (visible !== 1 ? 's' : '') + ' found';
                    } else {
                        updateSelected();
                    }
                }
            };

            search.addEventListener('input', applyFilters);

            // Topic chip click handler.
            topicChips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var slug = chip.getAttribute('data-topic-slug');
                    var idx = activeTopics.indexOf(slug);

                    if (idx !== -1) {
                        activeTopics.splice(idx, 1);
                        chip.classList.remove('meetings-topic-chip--active');
                        chip.setAttribute('aria-pressed', 'false');
                    } else {
                        activeTopics.push(slug);
                        chip.classList.add('meetings-topic-chip--active');
                        chip.setAttribute('aria-pressed', 'true');
                    }

                    // Update topic status bar.
                    if (topicStatus) {
                        if (activeTopics.length > 0) {
                            topicStatus.removeAttribute('hidden');
                            if (topicCountEl) {
                                topicCountEl.textContent = activeTopics.length + ' topic' + (activeTopics.length !== 1 ? 's' : '') + ' selected';
                            }
                        } else {
                            topicStatus.setAttribute('hidden', '');
                        }
                    }

                    applyFilters();
                });
            });

            // Clear all topics button.
            if (topicClearBtn) {
                topicClearBtn.addEventListener('click', function () {
                    activeTopics = [];
                    topicChips.forEach(function (chip) {
                        chip.classList.remove('meetings-topic-chip--active');
                        chip.setAttribute('aria-pressed', 'false');
                    });
                    if (topicStatus) {
                        topicStatus.setAttribute('hidden', '');
                    }
                    applyFilters();
                });
            }

            // Update selected count on change.
            var updateSelected = function () {
                var checked = picker.querySelectorAll('.council-picker__checkbox:checked');

                if (countEl && search.value.trim() === '' && activeTopics.length === 0) {
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

            // Initial count.
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
