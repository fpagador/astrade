/*
|--------------------------------------------------------------------------
| Create and Edit User -- create.blade.php edit.blade.php
|--------------------------------------------------------------------------
*/
document.addEventListener('DOMContentLoaded', function () {
    const page = document.body.dataset.page;
    const userPages = ['admin-users-create', 'admin-users-edit', 'admin-users-index'];

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
        if (!checkbox.checked) {
            notificationTypeSelect.value = 'none';
        }
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', toggleUserFields);
        toggleUserFields();
    }

    if (checkbox && notificationTypeSelect) {
        checkbox.addEventListener('change', toggleNotificationType);
        toggleNotificationType();
    }
});
