(function () {
    'use strict';

    function showFieldError(input, message) {
        clearFieldError(input);
        input.classList.add('is-invalid');
        const error = document.createElement('div');
        error.className = 'field-error';
        error.textContent = message;
        error.dataset.fieldError = 'true';
        input.insertAdjacentElement('afterend', error);
    }

    function clearFieldError(input) {
        input.classList.remove('is-invalid');
        const next = input.nextElementSibling;
        if (next && next.dataset.fieldError === 'true') {
            next.remove();
        }
    }

    function validateRegister(form) {
        let valid = true;
        const password = form.querySelector('#password');
        const confirm = form.querySelector('#confirm_password');

        if (password && confirm && password.value !== confirm.value) {
            showFieldError(confirm, 'Passwords do not match.');
            valid = false;
        }

        return valid;
    }

    function validateSearch(form) {
        let valid = true;
        const source = form.querySelector('#source_id');
        const dest = form.querySelector('#dest_id');

        if (source && dest && source.value && dest.value && source.value === dest.value) {
            showFieldError(dest, 'Departure and destination must differ.');
            valid = false;
        }

        return valid;
    }

    function validateBooking(form) {
        let valid = true;
        const seat = form.querySelector('#seat_no');

        if (seat && !/^[0-9]{1,2}[A-Fa-f]$/.test(seat.value.trim())) {
            showFieldError(seat, 'Use format like 12A.');
            valid = false;
        }

        return valid;
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            form.querySelectorAll('.field-error').forEach(function (el) {
                el.remove();
            });

            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            const type = form.dataset.validate;
            let customValid = true;

            if (type === 'register') {
                customValid = validateRegister(form);
            } else if (type === 'search') {
                customValid = validateSearch(form);
            } else if (type === 'booking') {
                customValid = validateBooking(form);
            }

            if (!customValid) {
                event.preventDefault();
            }
        });

        form.querySelectorAll('input, select').forEach(function (input) {
            input.addEventListener('input', function () {
                clearFieldError(input);
            });
        });
    });

    const travelDate = document.querySelector('#travel_date');
    if (travelDate && !travelDate.value) {
        const today = new Date().toISOString().split('T')[0];
        travelDate.min = today;
    }
})();
