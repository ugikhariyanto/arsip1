import './bootstrap';

const themeToggle = document.querySelector('.theme-toggle');
const sidebarToggle = document.querySelector('.sidebar-toggle');
const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
const sidebarLinks = document.querySelectorAll('.main-nav a');
const mobileViewport = window.matchMedia('(max-width: 800px)');

if (window.localStorage.getItem('arsip-sehat-theme') === 'dark') {
    document.body.classList.add('dark-theme');
}

if (window.localStorage.getItem('arsip-sehat-sidebar') === 'collapsed' && !mobileViewport.matches) {
    document.body.classList.add('sidebar-collapsed');
}

function setMobileNavigation(isOpen) {
    document.body.classList.toggle('mobile-nav-open', isOpen);
    sidebarToggle?.setAttribute('aria-expanded', String(isOpen || !mobileViewport.matches));
}

themeToggle?.addEventListener('click', () => {
    const isDark = document.body.classList.toggle('dark-theme');
    window.localStorage.setItem('arsip-sehat-theme', isDark ? 'dark' : 'light');
});

sidebarToggle?.addEventListener('click', () => {
    if (mobileViewport.matches) {
        setMobileNavigation(!document.body.classList.contains('mobile-nav-open'));
        return;
    }

    const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
    sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
    window.localStorage.setItem('arsip-sehat-sidebar', isCollapsed ? 'collapsed' : 'expanded');
});

sidebarBackdrop?.addEventListener('click', () => setMobileNavigation(false));
sidebarLinks.forEach((link) => link.addEventListener('click', () => setMobileNavigation(false)));
mobileViewport.addEventListener('change', () => setMobileNavigation(false));

const typeSelect = document.querySelector('#item-type');
const categorySelect = document.querySelector('#item-category');
const itemNumberDisplay = document.querySelector('#item-number-display');
const itemCodePrefix = document.querySelector('#item-code-prefix');
const itemCodePreview = document.querySelector('#item-code-preview');
const photoInput = document.querySelector('[data-photo-input]');
const photoPreview = document.querySelector('[data-photo-preview]');
const photoPlaceholder = document.querySelector('[data-photo-placeholder]');
const removePhotoCheckbox = document.querySelector('input[name="remove_photo"]');
const currencyInput = document.querySelector('[data-currency-input]');
const assetFields = document.querySelector('[data-asset-fields]');
const quantityInput = document.querySelector('input[name="quantity"]');
const quantityLabel = document.querySelector('#quantity-label');

function updateTypeFields() {
    if (!typeSelect || !assetFields || !quantityInput || !quantityLabel) {
        return;
    }

    const isAsset = typeSelect.value === 'asset';
    assetFields.hidden = !isAsset;
    quantityInput.min = isAsset ? '1' : '0';
    quantityLabel.innerHTML = isAsset ? 'Jumlah aset <b>*</b>' : 'Jumlah stok <b>*</b>';
}

typeSelect?.addEventListener('change', updateTypeFields);
updateTypeFields();

function updateItemCodePreview() {
    if (!categorySelect || !itemNumberDisplay || !itemCodePrefix || !itemCodePreview) {
        return;
    }

    const selectedOption = categorySelect.selectedOptions[0];
    const categoryCode = selectedOption?.dataset.code || '';
    const originalCategory = itemNumberDisplay.dataset.originalCategory || '';
    const isOriginalCategory = originalCategory !== '' && selectedOption?.value === originalCategory;
    const itemNumber = selectedOption
        ? (isOriginalCategory ? itemNumberDisplay.dataset.originalNumber : selectedOption.dataset.nextNumber) || ''
        : '';

    itemNumberDisplay.value = itemNumber;
    itemCodePrefix.value = categoryCode;
    itemCodePreview.textContent = selectedOption ? `${categoryCode}-${itemNumber}` : 'Pilih kategori';
}

categorySelect?.addEventListener('change', updateItemCodePreview);
updateItemCodePreview();

let photoPreviewUrl;

function updatePhotoPreview() {
    if (!photoInput || !photoPreview || !photoPlaceholder) {
        return;
    }

    const selectedPhoto = photoInput.files?.[0];

    if (photoPreviewUrl) {
        URL.revokeObjectURL(photoPreviewUrl);
        photoPreviewUrl = undefined;
    }

    if (selectedPhoto) {
        photoPreviewUrl = URL.createObjectURL(selectedPhoto);
        photoPreview.src = photoPreviewUrl;
        photoPreview.hidden = false;
        photoPlaceholder.hidden = true;
        if (removePhotoCheckbox) {
            removePhotoCheckbox.checked = false;
        }
        return;
    }

    if (removePhotoCheckbox?.checked) {
        photoPreview.hidden = true;
        photoPlaceholder.hidden = false;
        return;
    }

    const hasExistingPhoto = Boolean(photoPreview.getAttribute('src'));
    photoPreview.hidden = !hasExistingPhoto;
    photoPlaceholder.hidden = hasExistingPhoto;
}

photoInput?.addEventListener('change', updatePhotoPreview);
removePhotoCheckbox?.addEventListener('change', updatePhotoPreview);
updatePhotoPreview();

function formatRupiahInput(input) {
    const digits = input.value.replace(/\D/g, '');
    input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

currencyInput?.addEventListener('input', () => formatRupiahInput(currencyInput));

const numericKeypadKeys = [
    '7', '8', '9',
    '4', '5', '6',
    '1', '2', '3',
    'backspace', '0', 'clear',
];

function appendNumericDigit(input, digit) {
    input.value = input.value === '0' ? digit : input.value + digit;
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function buildNumericKeypad(input) {
    const keypad = document.createElement('div');
    keypad.className = 'numeric-keypad';
    keypad.hidden = true;
    keypad.setAttribute('role', 'group');
    keypad.setAttribute('aria-label', `Tombol angka untuk ${input.getAttribute('aria-label') || input.name || 'kolom ini'}`);

    numericKeypadKeys.forEach((key) => {
        const button = document.createElement('button');
        button.type = 'button';

        if (key === 'backspace') {
            button.textContent = '⌫';
            button.dataset.action = 'backspace';
            button.setAttribute('aria-label', 'Hapus satu angka');
        } else if (key === 'clear') {
            button.textContent = 'C';
            button.dataset.action = 'clear';
            button.setAttribute('aria-label', 'Hapus semua angka');
        } else {
            button.textContent = key;
            button.dataset.digit = key;
        }

        keypad.append(button);
    });

    // Jangan biarkan tombol merebut fokus, agar pengguna tetap bisa mengetik
    // lewat keyboard setelah menekan tombol di layar.
    keypad.addEventListener('pointerdown', (event) => {
        if (event.target.closest('button')) {
            event.preventDefault();
        }
    });

    keypad.addEventListener('click', (event) => {
        const button = event.target.closest('button');

        if (!button) {
            return;
        }

        if (button.dataset.digit !== undefined) {
            appendNumericDigit(input, button.dataset.digit);
        } else if (button.dataset.action === 'backspace') {
            input.value = input.value.slice(0, -1);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        } else if (button.dataset.action === 'clear') {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }

        input.focus();
    });

    return keypad;
}

document.querySelectorAll('input[data-numeric-keypad]').forEach((input) => {
    const keypad = buildNumericKeypad(input);
    const anchor = input.closest('.currency-input') ?? input;
    anchor.insertAdjacentElement('afterend', keypad);

    const showKeypad = () => {
        document.querySelectorAll('.numeric-keypad').forEach((other) => {
            if (other !== keypad) {
                other.hidden = true;
            }
        });
        keypad.hidden = false;
    };

    input.addEventListener('focus', showKeypad);
    input.addEventListener('click', showKeypad);
    input.addEventListener('blur', (event) => {
        if (!event.relatedTarget?.closest?.('.numeric-keypad')) {
            keypad.hidden = true;
        }
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            keypad.hidden = true;
        }
    });
});
