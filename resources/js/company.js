/*
|--------------------------------------------------------------------------
| Create Company -- create.blade.php and Edit Company -- edit.blade.php
|--------------------------------------------------------------------------
*/
export function initCompaniesPhones() {
    const phonesContainer = document.getElementById('phones-container');
    if (!phonesContainer) return; // Si no está el contenedor, no hacemos nada
    const addButton = document.getElementById('add-phone-button');
    if (!addButton) return;

    function updateRemoveButtonsVisibility() {
        const phoneCards = phonesContainer.querySelectorAll('.phone-card');
        if (phoneCards.length <= 1) {
            phoneCards.forEach(card => {
                const btn = card.querySelector('.remove-phone');
                if (btn) btn.style.display = 'none';
            });
        } else {
            phoneCards.forEach(card => {
                const btn = card.querySelector('.remove-phone');
                if (btn) btn.style.display = 'block';
            });
        }
    }

    addButton.addEventListener('click', () => {
        const index = phonesContainer.children.length;
        const phoneCard = document.createElement('div');
        phoneCard.classList.add('phone-card', 'relative', 'bg-indigo-100', 'border', 'border-indigo-300', 'rounded', 'p-4', 'flex', 'flex-col', 'gap-2');
        phoneCard.innerHTML = `
      <button type="button" class="remove-phone absolute top-1 right-1 text-red-600 hover:text-red-800 font-bold text-xl leading-none" title="Eliminar teléfono">&times;</button>
      <div>
        <label for="phones_${index}_name" class="block font-medium mb-1 flex items-center gap-1">Nombre</label>
        <input type="text" name="phones[${index}][name]" id="phones_${index}_name" class="form-input w-full" />
      </div>
      <div>
        <label for="phones_${index}_phone_number" class="block font-medium mb-1 flex items-center gap-1">Número</label>
        <input type="text" name="phones[${index}][phone_number]" id="phones_${index}_phone_number" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,9)" class="form-input w-full" />
      </div>
    `;
        phonesContainer.appendChild(phoneCard);
        phoneCard.querySelector('.remove-phone').addEventListener('click', () => {
            phoneCard.remove();
            updatePhoneIndexes();
            updateRemoveButtonsVisibility();
        });
        updateRemoveButtonsVisibility();
    });

    phonesContainer.querySelectorAll('.remove-phone').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.phone-card').remove();
            updatePhoneIndexes();
            updateRemoveButtonsVisibility();
        });
    });

    function updatePhoneIndexes() {
        Array.from(phonesContainer.children).forEach((card, idx) => {
            const nameInput = card.querySelector('input[name$="[name]"]');
            const phoneInput = card.querySelector('input[name$="[phone_number]"]');
            const nameLabel = card.querySelector('label[for^="phones_"][for$="_name"]');
            const phoneLabel = card.querySelector('label[for^="phones_"][for$="_phone_number"]');
            if (nameInput && phoneInput && nameLabel && phoneLabel) {
                nameInput.name = `phones[${idx}][name]`;
                phoneInput.name = `phones[${idx}][phone_number]`;
                nameInput.id = `phones_${idx}_name`;
                phoneInput.id = `phones_${idx}_phone_number`;
                nameLabel.htmlFor = nameInput.id;
                phoneLabel.htmlFor = phoneInput.id;
            }
        });
    }

    updateRemoveButtonsVisibility();
}
