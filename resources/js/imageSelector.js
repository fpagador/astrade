import {customAlert} from "./confirm.js";

export function imageSelector() {
    return {
        filename: document.getElementById('photo_name')?.value || 'Ningún archivo seleccionado',
        tempImageUrl: '',
        confirmedImageUrl: document.getElementById('photo_base64')?.value || '',
        showConfirmModal: false,

        async previewImage(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const file = input.files[0];

                const maxSize = 2 * 1024 * 1024;
                if (file.size > maxSize) {
                    await customAlert('La imagen no puede pesar más de 2 MB.');
                    input.value = '';
                    this.filename = 'Ningún archivo seleccionado';
                    this.tempImageUrl = '';
                    this.confirmedImageUrl = '';
                    this.showConfirmModal = false;
                    document.getElementById('photo_base64').value = '';
                    document.getElementById('photo_name').value = '';
                    return;
                }
                
                this.filename = file.name;

                const reader = new FileReader();
                reader.onload = e => {
                    this.tempImageUrl = e.target.result;
                    this.showConfirmModal = true;
                };
                reader.readAsDataURL(file);
            }
        },

        cancel() {
            this.tempImageUrl = '';
            this.showConfirmModal = false;
            this.filename = 'Ningún archivo seleccionado';
            const input = document.querySelector('input[type="file"]');
            if (input) input.value = '';
            document.getElementById('photo_base64').value = '';
            document.getElementById('photo_name').value = '';
        },

        confirm() {
            this.confirmedImageUrl = this.tempImageUrl;
            this.showConfirmModal = false;

            document.getElementById('photo_base64').value = this.tempImageUrl;
            document.getElementById('photo_name').value = this.filename;
        },

        openLarge(src) {
            window.dispatchEvent(
                new CustomEvent('open-image', { detail: { src, type: 'photo' } })
            );
        }
    };
}
