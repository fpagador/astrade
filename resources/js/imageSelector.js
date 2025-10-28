import { customAlert } from "./confirm.js";

export function imageSelector(base64Id = 'photo_base64', nameId = 'photo_name', original = '') {
    return {
        originalImage: original,
        confirmedImageUrl: original || '',
        tempImageUrl: '',
        showConfirmModal: false,
        filename: document.getElementById(nameId)?.value || (original ? 'Imagen existente' : 'Ningún archivo seleccionado'),

        imageToShow() {
            return this.tempImageUrl || this.confirmedImageUrl || this.originalImage || null;
        },

        async previewImage(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (file.size > maxSize) {
                    await customAlert('La imagen no puede pesar más de 2 MB.');
                    input.value = '';
                    this.tempImageUrl = '';
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

        confirm() {
            if (!this.tempImageUrl) return;
            this.confirmedImageUrl = this.tempImageUrl;
            document.getElementById(base64Id).value = this.tempImageUrl;
            document.getElementById(nameId).value = this.filename;
            this.tempImageUrl = '';
            this.showConfirmModal = false;
        },

        cancel() {
            this.tempImageUrl = '';
            this.showConfirmModal = false;

            if (this.confirmedImageUrl) {
                document.getElementById(base64Id).value = this.confirmedImageUrl;
                document.getElementById(nameId).value = this.filename;
            } else if (this.originalImage) {
                this.confirmedImageUrl = this.originalImage;
                this.filename = document.getElementById(nameId)?.value || 'Imagen existente';
                document.getElementById(base64Id).value = '';
                document.getElementById(nameId).value = this.filename;
            } else {
                this.confirmedImageUrl = '';
                this.filename = 'Ningún archivo seleccionado';
                document.getElementById(base64Id).value = '';
                document.getElementById(nameId).value = '';
            }

            const input = document.querySelector('input[type="file"]');
            if (input) input.value = '';
        },

        openLarge(src) {
            window.dispatchEvent(new CustomEvent('open-image', { detail: { src, type: 'photo' } }));
        }
    };
}
