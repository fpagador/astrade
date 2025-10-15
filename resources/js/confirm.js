export function customConfirm(message, showCancel = true) {
    return new Promise((resolve) => {
        const modal = document.getElementById('custom-confirm');
        const msg = document.getElementById('custom-confirm-message');
        const okBtn = document.getElementById('confirm-ok');
        const cancelBtn = document.getElementById('confirm-cancel');

        msg.textContent = message;
        modal.classList.remove('hidden');

        const cleanup = () => {
            modal.classList.add('hidden');
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
        };

        const onOk = () => {
            cleanup();
            resolve(true);
        };

        const onCancel = () => {
            cleanup();
            resolve(false);
        };

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
    });
}

export function customAlert(message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('custom-confirm');
        const msg = document.getElementById('custom-confirm-message');
        const okBtn = document.getElementById('confirm-ok');
        const cancelBtn = document.getElementById('confirm-cancel');

        msg.textContent = message;
        modal.classList.remove('hidden');

        // Solo mostramos el botón Ok
        okBtn.innerText = "Ok";
        cancelBtn.style.display = "none";

        const cleanup = () => {
            modal.classList.add('hidden');
            okBtn.removeEventListener('click', onOk);
        };

        const onOk = () => {
            cleanup();
            resolve(false);
        };

        okBtn.addEventListener('click', onOk);
    });
}
