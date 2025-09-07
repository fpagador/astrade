export function imageSelector() {
    return {
        filename: 'Ningún archivo seleccionado',
        tempImageUrl: '',
        confirmedImageUrl: '',
        showConfirmModal: false,

        previewImage(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const file = input.files[0];
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
        },

        confirm() {
            this.confirmedImageUrl = this.tempImageUrl;
            this.showConfirmModal = false;
        },

        openLarge(src) {
            window.dispatchEvent(
                new CustomEvent('open-image', { detail: { src, type: 'photo' } })
            );
        }
    };
}
