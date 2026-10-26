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
const itemNumberInput = document.querySelector('input[name="item_number"]');
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
    if (!categorySelect || !itemNumberInput || !itemCodePrefix || !itemCodePreview) {
        return;
    }

    const selectedOption = categorySelect.selectedOptions[0];
    const categoryCode = selectedOption?.dataset.code || '';
    const itemNumber = itemNumberInput.value || '001';

    itemCodePrefix.value = categoryCode;
    itemCodePreview.textContent = `${categoryCode || 'KODE'}-${itemNumber}`;
}

categorySelect?.addEventListener('change', updateItemCodePreview);
itemNumberInput?.addEventListener('input', updateItemCodePreview);
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
