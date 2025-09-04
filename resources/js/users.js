const photoInput = document.getElementById('photo');
const photoFilename = document.getElementById('photo-filename');

document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.querySelector(`[name="${btn.dataset.target}"]`);
        const icon = btn.querySelector('svg');

        if (target.type === 'password') {
            target.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            target.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }

        window.createIcons({ icons: window.lucideIcons });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const page = document.body.dataset.page;
    const userPages = ['admin-users-create', 'admin-users-edit', 'admin-users-edit-password'];
    if (!userPages.includes(page)) return;

    const roleSelect = document.getElementById('role_id') || document.getElementById('role');
    const userOnlyFields = document.querySelectorAll('.user-only');

    function toggleUserFields() {
        const selectedOption = roleSelect?.options[roleSelect.selectedIndex];
        const selectedRole = selectedOption?.dataset?.roleName?.toLowerCase();
        const isUser = selectedRole === 'user';
        userOnlyFields.forEach(el => {
            el.style.display = isUser ? 'grid' : 'none';
        });
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', toggleUserFields);
        toggleUserFields();
    }

    // Select Photo
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            if (photoInput.files.length > 0) {
                photoFilename.textContent = photoInput.files[0].name;
            } else {
                photoFilename.textContent = 'Ningún archivo seleccionado';
            }
        });
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

    // --- REAL-TIME PASSWORD VALIDATION ---
    if (page === 'admin-users-edit-password') {
        const password = document.querySelector('[name="password"]');
        const passwordConfirmation = document.querySelector('[name="password_confirmation"]');

        async function validatePasswordField(event) {
            const field = event.target.name;
            const data = {
                field: field,
                value: event.target.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value
            };

            try {
                const res = await fetch(window.routes.validatePassword, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const body = await res.json();
                removeError(password);
                removeError(passwordConfirmation);

                if (body.error) {
                    if (field === 'password') showError(password, body.error);
                    if (field === 'password_confirmation') showError(passwordConfirmation, body.error);
                }
            } catch (err) {
                console.error('Unexpected error:', err);
            }
        }

        password.addEventListener('blur', validatePasswordField);
        passwordConfirmation.addEventListener('blur', validatePasswordField);
    }

    // --- REAL-TIME FIELDS VALIDATION ---
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

        if (!value) {
            const label = fieldLabels[field] || field;
            showError(input, `El campo ${label} es obligatorio.`);
            return;
        }

        const userIdInput = document.querySelector('input[name="id"]');
        const userId = userIdInput ? userIdInput.value : null;

        const typeInput = document.querySelector('input[name="type"]');
        const type = typeInput ? typeInput.value : null;

        const data = {
            field: field,
            value: value,
            id: userId,
            type: type
        };

        if (field === 'password' || field === 'password_confirmation') {
            data.password = passwordValue;
            data.password_confirmation = confirmationValue;
        }

        const otherFields = ['dni', 'email', 'username', 'phone'];
        otherFields.forEach(f => {
            if (f !== field) {
                const el = document.querySelector(`[name="${f}"]`);
                if (el) data[f] = el.value;
            }
        });

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
    if (['admin-users-create', 'admin-users-edit'].includes(page)) {
        const inputs = document.querySelectorAll(
            '#dni, #email, #username, #phone, [name="password"], [name="password_confirmation"]'
        );

        inputs.forEach(input => {
            input.addEventListener('blur', function () {
                validateField(this);
                if (this.name === 'password') {
                    const confirmation = document.querySelector('[name="password_confirmation"]');
                    if (confirmation && confirmation.value) validateField(confirmation);
                }
            });
        });
    }
});
