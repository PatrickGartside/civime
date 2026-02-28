/* CiviMe Meetings — Plugin Scripts */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Auto-submit filter forms on select change
        const filterForms = document.querySelectorAll('.meetings-filters, .councils-filters');

        filterForms.forEach((form) => {
            form.querySelectorAll('select').forEach((select) => {
                select.addEventListener('change', () => form.submit());
            });
        });

        // 2. Combobox — searchable council dropdown (ARIA 1.2 combobox pattern)
        document.querySelectorAll('[data-combobox]').forEach((container) => {
            const input = container.querySelector('.combobox__input');
            const hidden = container.querySelector('[data-combobox-value]');
            const listbox = container.querySelector('.combobox__listbox');
            const options = Array.from(listbox.querySelectorAll('[role="option"]'));
            let highlightedIndex = -1;

            const showListbox = () => {
                listbox.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            };

            const hideListbox = () => {
                listbox.hidden = true;
                input.setAttribute('aria-expanded', 'false');
                highlightedIndex = -1;
                options.forEach((o) => o.removeAttribute('data-highlighted'));
            };

            const selectOption = (option) => {
                const value = option.dataset.value;
                const text = value === '' ? '' : option.textContent.trim();
                hidden.value = value;
                input.value = text;
                input.placeholder = value === '' ? input.dataset.placeholder || 'All Councils' : '';
                hideListbox();
                // Submit the parent form.
                const form = container.closest('form');
                if (form) form.submit();
            };

            const filterOptions = () => {
                const query = input.value.toLowerCase().trim();
                let visibleCount = 0;

                // Track which groups have visible options.
                const groupLabels = listbox.querySelectorAll('.combobox__group-label');
                const groupVisibility = new Map();
                groupLabels.forEach((g) => groupVisibility.set(g, false));

                let currentGroup = null;
                listbox.childNodes.forEach((node) => {
                    if (node.nodeType !== 1) return;
                    if (node.classList.contains('combobox__group-label')) {
                        currentGroup = node;
                        return;
                    }
                    if (node.getAttribute('role') !== 'option') return;

                    const text = node.textContent.toLowerCase();
                    const match = query === '' || text.includes(query);
                    node.hidden = !match;
                    if (match) {
                        visibleCount++;
                        if (currentGroup) groupVisibility.set(currentGroup, true);
                    }
                });

                // Show/hide group labels based on whether they have visible children.
                groupVisibility.forEach((visible, label) => {
                    label.hidden = !visible;
                });

                highlightedIndex = -1;
                options.forEach((o) => o.removeAttribute('data-highlighted'));
            };

            const getVisibleOptions = () => options.filter((o) => !o.hidden);

            const highlightOption = (index) => {
                const visible = getVisibleOptions();
                options.forEach((o) => o.removeAttribute('data-highlighted'));
                if (index >= 0 && index < visible.length) {
                    highlightedIndex = index;
                    visible[index].setAttribute('data-highlighted', '');
                    visible[index].scrollIntoView({ block: 'nearest' });
                    input.setAttribute('aria-activedescendant', visible[index].id || '');
                }
            };

            // Save original placeholder.
            input.dataset.placeholder = input.placeholder;

            input.addEventListener('focus', () => {
                showListbox();
                filterOptions();
            });

            input.addEventListener('input', () => {
                // Clear the hidden value when user types (they're searching).
                hidden.value = '';
                showListbox();
                filterOptions();
            });

            input.addEventListener('keydown', (e) => {
                const visible = getVisibleOptions();
                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        if (listbox.hidden) { showListbox(); filterOptions(); }
                        highlightOption(Math.min(highlightedIndex + 1, visible.length - 1));
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        highlightOption(Math.max(highlightedIndex - 1, 0));
                        break;
                    case 'Enter':
                        e.preventDefault();
                        if (highlightedIndex >= 0 && visible[highlightedIndex]) {
                            selectOption(visible[highlightedIndex]);
                        }
                        break;
                    case 'Escape':
                        hideListbox();
                        input.blur();
                        break;
                }
            });

            listbox.addEventListener('mousedown', (e) => {
                // Prevent blur on the input when clicking an option.
                e.preventDefault();
            });

            listbox.addEventListener('click', (e) => {
                const option = e.target.closest('[role="option"]');
                if (option) selectOption(option);
            });

            // Close when clicking outside.
            document.addEventListener('click', (e) => {
                if (!container.contains(e.target)) hideListbox();
            });
        });

        // 3. Clear button visibility — show only when any filter has a non-default value
        filterForms.forEach((form) => {
            const clearButton = form.querySelector('.filter-clear');
            if (!clearButton) return;

            const updateClearVisibility = () => {
                const hasActiveFilter = Array.from(form.querySelectorAll('select, input[type="text"], input[type="search"]'))
                    .some((field) => field.value.trim() !== '' && field.value !== field.dataset.default);

                clearButton.style.display = hasActiveFilter ? '' : 'none';
            };

            form.querySelectorAll('select, input[type="text"], input[type="search"]').forEach((field) => {
                field.addEventListener('change', updateClearVisibility);
                field.addEventListener('input', updateClearVisibility);
            });
        });

        // 4. Share button — Web Share API with copy-to-clipboard fallback
        document.querySelectorAll('.js-share-meeting').forEach((btn) => {
            btn.addEventListener('click', () => {
                var data = {
                    title: btn.dataset.title,
                    text: btn.dataset.text,
                    url: btn.dataset.url,
                };

                if (navigator.share) {
                    navigator.share(data).catch(function () {});
                    return;
                }

                navigator.clipboard.writeText(data.url).then(function () {
                    var original = btn.textContent;
                    btn.textContent = 'Link Copied!';
                    setTimeout(function () { btn.textContent = original; }, 2000);
                });
            });
        });

        // 5. Smooth scroll to results when filter params (not just pagination) are active
        const params = new URLSearchParams(window.location.search);
        const filterKeys = ['q', 'council_id', 'date_from', 'date_to', 'county'];
        const hasActiveFilter = filterKeys.some((k) => params.has(k) && params.get(k) !== '');

        if (hasActiveFilter) {
            const resultsTarget = document.querySelector('.meetings-date-group, .councils-grid');
            if (resultsTarget) {
                setTimeout(() => {
                    resultsTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        }
    });
})();
