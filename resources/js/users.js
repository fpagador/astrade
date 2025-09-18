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

/**
 * Alpine.js component to handle user selection per company.
 * Allows:
 *  - Selecting users by company.
 *  - Searching users by name or surname.
 *  - Adding/removing users dynamically.
 *  - Assigning a unique color per company for UI badges.
 */
export function userSelector() {
    return {
        companies: [],
        allUsers: [],
        selectedCompany: null,
        selectedUsers: [],
        searchQuery: '',
        searchResults: [],
        companyColorMap: {},
        colors: [
            'bg-indigo-100 text-indigo-900',
            'bg-green-100 text-green-900',
            'bg-red-100 text-red-900',
            'bg-pink-100 text-pink-900',
            'bg-purple-100 text-purple-900'
        ],

        /**
         * Initialize component.
         * - parameters are optional (they may be undefined if Alpine starts early)
         * - tries to use passed values first, then falls back to window.* variables
         * - retries a few times if window vars arrive shortly after Alpine init
         */
        init(companies = null, users = null) {
            // Prefer explicit arguments, fallback to window variables
            this.companies = Array.isArray(companies) ? companies : (Array.isArray(window.appCompanies) ? window.appCompanies : []);
            this.allUsers  = Array.isArray(users)     ? users     : (Array.isArray(window.appUsers)     ? window.appUsers     : []);

            // If we don't have data yet, retry a few times (in case inline script is later)
            if ((!this.companies.length && !Array.isArray(companies)) || (!this.allUsers.length && !Array.isArray(users))) {
                let retries = 0;
                const tryInit = () => {
                    retries++;
                    if (Array.isArray(window.appCompanies) && window.appCompanies.length) this.companies = window.appCompanies;
                    if (Array.isArray(window.appUsers) && window.appUsers.length) this.allUsers = window.appUsers;

                    if (this.companies.length || this.allUsers.length || retries >= 10) {
                        // assign colors after we've loaded companies (or after last attempt)
                        this._assignColors();
                    } else {
                        setTimeout(tryInit, 50);
                    }
                };
                tryInit();
            } else {
                this._assignColors();
            }
        },

        /**
         * Internal helper to map a CSS class color to each company id.
         */
        _assignColors() {
            this.companyColorMap = {};
            this.companies.forEach((c, i) => {
                // guard: ensure c.id exists
                const id = (c && (c.id ?? c.company_id ?? null));
                if (id !== null && id !== undefined) {
                    this.companyColorMap[id] = this.colors[i % this.colors.length];
                }
            });
        },

        /**
         * Add all users that belong to the selected company to selectedUsers.
         */
        loadCompanyUsers() {
            if (!this.selectedCompany) return;
            const companyUsers = this.allUsers.filter(u => u.company_id == this.selectedCompany);
            companyUsers.forEach(u => {
                if (!this.selectedUsers.find(su => su.id == u.id)) {
                    this.selectedUsers.push(u);
                }
            });
        },

        /**
         * Remove a user from the selection.
         */
        removeUser(user) {
            this.selectedUsers = this.selectedUsers.filter(u => u.id != user.id);
        },

        /**
         * Search users by name or surname, skipping already selected users.
         */
        searchUsers() {
            const q = (this.searchQuery || '').toLowerCase().trim();
            if (!q) {
                this.searchResults = [];
                return;
            }
            this.searchResults = this.allUsers.filter(u =>
                (String(u.name || '').toLowerCase().includes(q) ||
                    String(u.surname || '').toLowerCase().includes(q)) &&
                !this.selectedUsers.find(su => su.id == u.id)
            );
        },

        /**
         * Add an individual user from search results to the selection.
         */
        addUser(user) {
            if (!this.selectedUsers.find(u => u.id == user.id)) {
                this.selectedUsers.push(user);
            }
            this.searchQuery = '';
            this.searchResults = [];
        },

        /**
         * Return CSS class for a company's badge based on the precomputed map.
         */
        companyColor(companyId) {
            return this.companyColorMap[companyId] || 'bg-gray-200 text-gray-800';
        },

        /**
         * Return company name given id.
         */
        getCompanyName(companyId) {
            const company = this.companies.find(c => c.id == companyId);
            return company ? company.name : '';
        }
    };
}
