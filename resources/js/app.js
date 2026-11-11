import './bootstrap';

const themeToggle = document.querySelector('.theme-toggle');
const sidebarToggle = document.querySelector('.sidebar-toggle');
const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
const sidebarLinks = document.querySelectorAll('.main-nav a');
const mobileViewport = window.matchMedia('(max-width: 800px)');

function setMobileNavigation(isOpen) {
    document.body.classList.toggle('mobile-nav-open', isOpen);
    sidebarToggle?.setAttribute('aria-expanded', String(isOpen || !mobileViewport.matches));
}

themeToggle?.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark-theme');
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
const assetSectionTitle = document.querySelector('[data-asset-section-title]');
const locationField = document.querySelector('[data-location-field]');
const locationSelect = document.querySelector('select[name="location_id"]');
const calibrationFields = document.querySelectorAll('[data-calibration-field]');
const calibrationSelect = document.querySelector('#item-calibration-status');
const calibrationDateInput = document.querySelector('input[name="calibration_date"]');
const quantityInput = document.querySelector('input[name="quantity"]');
const quantityLabel = document.querySelector('#quantity-label');
const conditionSelect = document.querySelector('#item-condition');
const conditionDetailFields = document.querySelectorAll('[data-condition-detail]');
const conditionDateLabel = document.querySelector('[data-condition-date-label]');
const conditionDateHint = document.querySelector('[data-condition-date-hint]');

const damagedConditions = ['Rusak sedang', 'Rusak berat'];
const lostConditions = ['Hilang', 'Tidak ditemukan'];

function updateTypeFields() {
    if (!typeSelect || !assetFields || !quantityInput || !quantityLabel) {
        return;
    }

    const isConsumable = typeSelect.value === 'consumable';
    const isAsset = typeSelect.value === 'asset';
    const isFacility = typeSelect.value === 'facility';
    const hasLocation = isAsset || isFacility;

    assetFields.hidden = isConsumable;
    quantityInput.min = isConsumable ? '0' : '1';
    quantityLabel.innerHTML = isConsumable
        ? 'Jumlah stok <b>*</b>'
        : (isAsset ? 'Jumlah aset <b>*</b>' : 'Jumlah sarana <b>*</b>');

    if (assetSectionTitle) {
        assetSectionTitle.textContent = isFacility ? 'Informasi sarana dan prasarana' : 'Informasi aset';
    }

    if (locationField) {
        locationField.hidden = !hasLocation;
    }

    if (locationSelect) {
        locationSelect.disabled = !hasLocation;
    }

    calibrationFields.forEach((field) => {
        field.hidden = !isAsset;
    });

    if (calibrationSelect) {
        calibrationSelect.disabled = !isAsset;
    }

    if (calibrationDateInput) {
        calibrationDateInput.disabled = !isAsset;
    }
}

function updateConditionFields() {
    if (!conditionSelect) {
        return;
    }

    const condition = conditionSelect.value;
    const needsDetail = damagedConditions.includes(condition) || lostConditions.includes(condition);
    const isLost = lostConditions.includes(condition);

    conditionDetailFields.forEach((field) => {
        field.hidden = !needsDetail;
    });

    if (conditionDateLabel) {
        conditionDateLabel.innerHTML = `${isLost ? 'Tanggal hilang' : 'Tanggal rusak'} <b>*</b>`;
    }

    if (conditionDateHint) {
        conditionDateHint.textContent = isLost
            ? 'Isi tanggal barang dinyatakan hilang atau tidak ditemukan.'
            : 'Isi tanggal barang mulai rusak.';
    }
}

typeSelect?.addEventListener('change', updateTypeFields);
conditionSelect?.addEventListener('change', updateConditionFields);
updateTypeFields();
updateConditionFields();

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

function rgbToHsl(red, green, blue) {
    const r = red / 255;
    const g = green / 255;
    const b = blue / 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const lightness = (max + min) / 2;
    let hue = 0;
    let saturation = 0;

    if (max !== min) {
        const delta = max - min;
        saturation = lightness > 0.5 ? delta / (2 - max - min) : delta / (max + min);

        if (max === r) {
            hue = (g - b) / delta + (g < b ? 6 : 0);
        } else if (max === g) {
            hue = (b - r) / delta + 2;
        } else {
            hue = (r - g) / delta + 4;
        }

        hue *= 60;
    }

    return [hue, saturation, lightness];
}

function photoShadowColor(image) {
    const size = 24;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;

    const context = canvas.getContext('2d', { willReadFrequently: true });

    if (!context) {
        return null;
    }

    context.drawImage(image, 0, 0, size, size);
    const { data } = context.getImageData(0, 0, size, size);
    let red = 0;
    let green = 0;
    let blue = 0;
    let totalWeight = 0;

    for (let index = 0; index < data.length; index += 4) {
        if (data[index + 3] < 128) {
            continue;
        }

        const r = data[index];
        const g = data[index + 1];
        const b = data[index + 2];
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);

        // Lewati area yang hampir hitam atau hampir putih (biasanya latar foto).
        if (max < 24 || min > 242) {
            continue;
        }

        // Warna yang lebih pekat diberi bobot lebih besar agar warna benda
        // lebih dominan daripada latar yang pucat.
        const weight = 0.25 + (max - min) / 255;
        red += r * weight;
        green += g * weight;
        blue += b * weight;
        totalWeight += weight;
    }

    if (totalWeight === 0) {
        return null;
    }

    const [hue, saturation, lightness] = rgbToHsl(red / totalWeight, green / totalWeight, blue / totalWeight);

    if (saturation < 0.06) {
        return null;
    }

    const shadowSaturation = Math.min(90, Math.max(45, saturation * 100 + 12));
    const shadowLightness = Math.min(62, Math.max(42, lightness * 100));

    return `hsl(${Math.round(hue)} ${Math.round(shadowSaturation)}% ${Math.round(shadowLightness)}%)`;
}

function applyPhotoShadow(image) {
    const row = image.closest('tr');

    if (!row) {
        return;
    }

    try {
        const color = photoShadowColor(image);

        if (color) {
            row.style.setProperty('--row-color', color);
        }
    } catch (error) {
        // Gambar dari domain lain tanpa izin CORS tidak bisa dibaca canvas.
        // Baris tetap memakai warna bayangan default.
    }
}

document.querySelectorAll('img.item-thumbnail').forEach((image) => {
    if (!(image instanceof HTMLImageElement)) {
        return;
    }

    if (image.complete && image.naturalWidth > 0) {
        applyPhotoShadow(image);
        return;
    }

    image.addEventListener('load', () => applyPhotoShadow(image), { once: true });
});
