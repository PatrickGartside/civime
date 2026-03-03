/* CiviMe Notifications — Notify Me Modal */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var modal = document.getElementById('notify-modal');
        if (!modal) return;

        var backdrop     = modal.querySelector('.notify-modal__backdrop');
        var dialog       = modal.querySelector('.notify-modal__dialog');
        var closeBtn     = modal.querySelector('.notify-modal__close');
        var form         = document.getElementById('notify-modal-form');
        var emailInput   = document.getElementById('notify-modal-email');
        var emailError   = document.getElementById('notify-modal-email-error');
        var meetingIdEl  = document.getElementById('notify-modal-meeting-id');
        var successEl    = document.getElementById('notify-modal-success');
        var errorEl      = document.getElementById('notify-modal-error');
        var errorMsgEl   = document.getElementById('notify-modal-error-message');
        var subscribeEl  = document.getElementById('notify-modal-subscribe');
        var councilName  = modal.querySelector('.notify-modal__council-name');
        var meetingDate  = modal.querySelector('.notify-modal__meeting-date');
        var subCouncil   = modal.querySelector('.notify-modal__subscribe-council');
        var submitBtn    = modal.querySelector('.notify-modal__submit');

        var triggerElement = null;

        // =====================================================================
        // Open / Close
        // =====================================================================

        function open(trigger) {
            triggerElement = trigger;

            // Populate meeting context from trigger data attributes.
            var data = trigger.dataset;
            if (councilName)  councilName.textContent  = data.councilName || '';
            if (meetingDate)  meetingDate.textContent   = data.meetingDate || '';
            if (meetingIdEl)  meetingIdEl.value         = data.meetingId   || '';
            if (subCouncil)   subCouncil.textContent    = data.councilName || '';
            if (subscribeEl)  subscribeEl.href          = data.subscribeUrl || '#';

            // Reset form and states.
            form.reset();
            form.removeAttribute('hidden');
            successEl.setAttribute('hidden', '');
            errorEl.setAttribute('hidden', '');
            emailError.setAttribute('hidden', '');
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtn.getAttribute('data-label') || submitBtn.textContent;

            // Show modal.
            modal.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';

            // Focus the email input after a frame so the browser paints first.
            requestAnimationFrame(function () {
                emailInput.focus();
            });

            // Listen for ESC and focus trap.
            document.addEventListener('keydown', onKeyDown);
        }

        function close() {
            modal.setAttribute('hidden', '');
            document.body.style.overflow = '';
            document.removeEventListener('keydown', onKeyDown);

            // Return focus to the trigger element.
            if (triggerElement) {
                triggerElement.focus();
                triggerElement = null;
            }
        }

        closeBtn.addEventListener('click', close);
        backdrop.addEventListener('click', close);

        // =====================================================================
        // Focus Trap
        // =====================================================================

        function getFocusable() {
            return dialog.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]):not([tabindex="-1"]), [tabindex]:not([tabindex="-1"])'
            );
        }

        function onKeyDown(e) {
            if (e.key === 'Escape') {
                close();
                return;
            }

            if (e.key !== 'Tab') return;

            var focusable = getFocusable();
            if (!focusable.length) return;

            var first = focusable[0];
            var last  = focusable[focusable.length - 1];

            if (e.shiftKey) {
                if (document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        }

        // =====================================================================
        // Trigger Binding (progressive enhancement)
        // =====================================================================

        // Store original button label for reset.
        if (submitBtn && !submitBtn.getAttribute('data-label')) {
            submitBtn.setAttribute('data-label', submitBtn.textContent.trim());
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-open-notify-modal');
            if (!trigger) return;

            e.preventDefault();
            open(trigger);
        });

        // =====================================================================
        // Form Submission (AJAX via fetch)
        // =====================================================================

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Client-side email validation.
            var email = emailInput.value.trim();
            if (!email || !isValidEmail(email)) {
                showFieldError('Please enter a valid email address.');
                emailInput.focus();
                return;
            }

            clearFieldError();

            // Disable button and show loading state.
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending\u2026';

            var formData = new FormData(form);
            formData.append('action', 'civime_create_reminder');
            formData.append('_ajax_nonce', civimeModal.nonce);

            fetch(civimeModal.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    // Show success state, hide form.
                    form.setAttribute('hidden', '');
                    errorEl.setAttribute('hidden', '');
                    successEl.removeAttribute('hidden');
                } else {
                    var msg = (data.data && data.data.message) || 'Something went wrong. Please try again.';
                    showAjaxError(msg);
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.getAttribute('data-label');
                }
            })
            .catch(function () {
                showAjaxError('Something went wrong. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.getAttribute('data-label');
            });
        });

        // =====================================================================
        // Validation Helpers
        // =====================================================================

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function showFieldError(msg) {
            emailError.textContent = msg;
            emailError.removeAttribute('hidden');
            emailInput.setAttribute('aria-describedby', 'notify-modal-email-error');
        }

        function clearFieldError() {
            emailError.setAttribute('hidden', '');
            emailError.textContent = '';
            emailInput.removeAttribute('aria-describedby');
        }

        function showAjaxError(msg) {
            errorMsgEl.textContent = msg;
            errorEl.removeAttribute('hidden');
        }
    });
})();
