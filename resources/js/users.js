document.addEventListener('DOMContentLoaded', function () {
    const page = document.body.dataset.page;
    const userPages = ['admin-users-create', 'admin-users-edit', 'admin-users-edit-password'];
    if (!userPages.includes(page)) return;

    const roleSelect = document.getElementById('role_id') || document.getElementById('role');
    const userOnlyFields = document.querySelectorAll('.user-only');
    const checkbox = document.getElementById('can_receive_notifications');
    const notificationTypeSelect = document.getElementById('notification_type');

    function toggleUserFields() {
        const selectedOption = roleSelect?.options[roleSelect.selectedIndex];
        const selectedRole = selectedOption?.dataset?.roleName?.toLowerCase();
        const isUser = selectedRole === 'user';
        userOnlyFields.forEach(el => {
            el.style.display = isUser ? 'block' : 'none';
        });
    }

    function toggleNotificationType() {
        if (!checkbox || !notificationTypeSelect) return;
        notificationTypeSelect.disabled = !checkbox.checked;
        if (!checkbox.checked) notificationTypeSelect.value = 'none';
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', toggleUserFields);
        toggleUserFields();
    }

    if (checkbox && notificationTypeSelect) {
        checkbox.addEventListener('change', toggleNotificationType);
        toggleNotificationType();
    }

    // --- REAL-TIME VALIDATION ---
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function showError(input, message) {
        removeError(input);
        const p = document.createElement('p');
        p.classList.add('text-red-600', 'text-sm', 'mt-1');
        p.textContent = message;
        input.parentNode.appendChild(p);
    }

    function removeError(input) {
        const existing = input.parentNode.querySelector('.text-red-600');
        if (existing) existing.remove();
    }

    function validateField(input) {
        if (input.closest('.user-only') && input.style.display === 'none') return;

        const field = input.name;
        const value = input.value;

        // Display basic message in Spanish
        const fieldLabels = {
            dni: 'DNI',
            email: 'Email',
            phone: 'Teléfono',
            username: 'Usuario',
            password: 'Contraseña',
            password_confirmation: 'Confirmar contraseña',
        };

        const password = document.querySelector('[name="password"]');
        const confirmation = document.querySelector('[name="password_confirmation"]');
        const passwordValue = password?.value || '';
        const confirmationValue = confirmation?.value || '';

        if ((field === 'password' || field === 'password_confirmation') && (!passwordValue || !confirmationValue)) {
            removeError(password);
            removeError(confirmation);
            return;
        }

        if (!value) {
            const label = fieldLabels[field] || field;
            showError(input, `El campo ${label} es obligatorio.`);
            return;
        }

        // Prepara datos para enviar
        const data = { field, value };
        if (field === 'password' || field === 'password_confirmation') {
            data.password = passwordValue;
            data.password_confirmation = confirmationValue;
        }

        fetch(window.routes.validateField, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    showError(input, data.error);
                } else {
                    removeError(input);
                }
            })
            .catch(err => console.error(err));
    }

    // Inputs to be validated in real time
    const inputs = document.querySelectorAll(
        '#dni, #email, #username, #phone, [name="password"], [name="password_confirmation"]'
    );

    inputs.forEach(input => {
        input.addEventListener('blur', function () {
            validateField(this);
        });
    });
});
