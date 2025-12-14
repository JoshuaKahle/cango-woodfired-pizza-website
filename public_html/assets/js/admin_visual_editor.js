
const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));

const btnDeleteCategory = document.getElementById('btnDeleteCategory');

const adminToastEl = document.getElementById('adminToast');
const adminToastHeaderEl = document.getElementById('adminToastHeader');
const adminToastTitleEl = document.getElementById('adminToastTitle');
const adminToastBodyEl = document.getElementById('adminToastBody');
const adminToast = adminToastEl ? new bootstrap.Toast(adminToastEl, { delay: 4500 }) : null;

const confirmModalEl = document.getElementById('confirmModal');
const confirmModalLabelEl = document.getElementById('confirmModalLabel');
const confirmModalBodyEl = document.getElementById('confirmModalBody');
const confirmModalOkBtn = document.getElementById('confirmModalOk');
const confirmModalCancelBtn = document.getElementById('confirmModalCancel');
const confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;

function showToast(message, type = 'info', title = 'Notice') {
    if (!adminToast || !adminToastHeaderEl || !adminToastTitleEl || !adminToastBodyEl) return;

    adminToastHeaderEl.classList.remove('toast-success', 'toast-danger');
    if (type === 'success') adminToastHeaderEl.classList.add('toast-success');
    if (type === 'danger' || type === 'error') adminToastHeaderEl.classList.add('toast-danger');

    adminToastTitleEl.textContent = String(title || 'Notice');
    adminToastBodyEl.textContent = String(message || '');
    adminToast.show();
}

function confirmDialog(message, title = 'Please Confirm', okText = 'Confirm', cancelText = 'Cancel') {
    return new Promise((resolve) => {
        if (!confirmModal || !confirmModalEl || !confirmModalLabelEl || !confirmModalBodyEl || !confirmModalOkBtn || !confirmModalCancelBtn) {
            resolve(false);
            return;
        }

        confirmModalLabelEl.textContent = String(title || 'Please Confirm');
        confirmModalBodyEl.textContent = String(message || '');
        confirmModalOkBtn.textContent = String(okText || 'Confirm');
        confirmModalCancelBtn.textContent = String(cancelText || 'Cancel');

        let result = false;

        confirmModalOkBtn.onclick = function () {
            result = true;
            confirmModal.hide();
        };

        confirmModalEl.addEventListener('hidden.bs.modal', function onHidden() {
            confirmModalEl.removeEventListener('hidden.bs.modal', onHidden);
            confirmModalOkBtn.onclick = null;
            resolve(result);
        });

        confirmModal.show();
    });
}

function withCsrfHeaders(headers) {
    const out = headers ? Object.assign({}, headers) : {};
    if (typeof CSRF_TOKEN !== 'undefined' && CSRF_TOKEN) {
        out['X-CSRF-Token'] = CSRF_TOKEN;
    }
    return out;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openItemModal(categoryId, item = null, allowedVariants = [], type = 'menu') {
    document.getElementById('itemForm').reset();
    document.getElementById('itemCategoryId').value = categoryId;
    
    // Set display for Delete button
    // Set display for Delete button
    const btnDelete = document.getElementById('btnDelete');
    btnDelete.style.display = item ? 'block' : 'none';

    // UI Elements
    const nameLabel = document.getElementById('itemNameLabel');
    const nameInput = document.getElementById('itemNameInput');
    const nameTextarea = document.getElementById('itemNameTextarea');
    const descContainer = document.getElementById('itemDescriptionContainer');
    
    if (type === 'special') {
        nameLabel.innerText = 'Special Details';
        // different field logic
        nameInput.style.display = 'none';
        nameInput.disabled = true;
        nameTextarea.style.display = 'block';
        nameTextarea.disabled = false;
        
        descContainer.style.display = 'none';
    } else {
        nameLabel.innerText = 'Item Name';
        // normal logic
        nameInput.style.display = 'block';
        nameInput.disabled = false;
        nameTextarea.style.display = 'none';
        nameTextarea.disabled = true;
        
        descContainer.style.display = 'block';
    }

    // Clear and prepare variants container
    const container = document.getElementById('variants-container');
    container.innerHTML = '';

    // Determine variant values
    // Create a map of existing variants for easy lookup: { 'size_id': price }
    const existingVariants = {};
    if (item && item.variants) {
        item.variants.forEach(v => {
            existingVariants[v.size_id] = v.price;
        });
    }

    // Render inputs for ALLOWED variants only
    if (allowedVariants && allowedVariants.length > 0 && typeof SIZE_DEFINITIONS !== 'undefined') {
        allowedVariants.forEach(sizeId => {
            // Find definition
            const def = SIZE_DEFINITIONS.find(d => d.id == sizeId);
            if (!def) return;

            const price = existingVariants[sizeId] || '';
            const safeName = escapeHtml(def.name);
            const safeMeasurement = escapeHtml(def.measurement);
            
            const div = document.createElement('div');
            div.className = 'mb-2 row align-items-center variant-row';
            div.innerHTML = `
                <div class="col-6">
                    <label class="form-label mb-0">${safeName} <span class="text-muted small">(${safeMeasurement})</span></label>
                    <input type="hidden" name="variant_size_id[]" value="${def.id}">
                </div>
                <div class="col-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">R</span>
                        <input type="number" step="0.01" class="form-control" name="variant_price[]" placeholder="0.00" value="${price}">
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    } else if (typeof SIZE_DEFINITIONS !== 'undefined') {
        // No specific variants -> Single Price (Standard)
        const stdDef = SIZE_DEFINITIONS.find(d => d.name === 'Standard') || SIZE_DEFINITIONS[0]; // Fallback to first if Standard not found
        if (stdDef) {
            const price = existingVariants[stdDef.id] || existingVariants[Object.keys(existingVariants)[0]] || ''; // Try exact match or first existing
            
            const div = document.createElement('div');
            div.className = 'mb-2 row align-items-center variant-row';
            div.innerHTML = `
                <div class="col-6">
                    <label class="form-label mb-0">Price</label>
                    <input type="hidden" name="variant_size_id[]" value="${stdDef.id}">
                </div>
                <div class="col-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">R</span>
                        <input type="number" step="0.01" class="form-control" name="variant_price[]" placeholder="0.00" value="${price}">
                    </div>
                </div>
            `;
            container.appendChild(div);
        } else {
             container.innerHTML = '<p class="text-danger small">Error: No size definitions found.</p>';
        }
    } else {
        container.innerHTML = '<p class="text-muted small">No sizes configured for this category.</p>';
    }

    if (item) {
        document.getElementById('itemModalLabel').innerText = type === 'special' ? 'Edit Special' : 'Edit Item';
        document.getElementById('itemId').value = item.id;
        
        // Populate correct name field
        document.getElementById('itemNameInput').value = item.name;
        document.getElementById('itemNameTextarea').value = item.name;
        
        document.getElementById('itemDescription').value = item.description || '';
        document.getElementById('itemIsActive').checked = item.is_active == 1;

    } else {
        document.getElementById('itemModalLabel').innerText = type === 'special' ? 'Add New Special' : 'Add New Item';
        document.getElementById('itemId').value = '';
        document.getElementById('itemIsActive').checked = true;
         // Clear fields
        document.getElementById('itemNameInput').value = '';
        document.getElementById('itemNameTextarea').value = '';
        document.getElementById('itemDescription').value = '';
    }

    itemModal.show();
}

// addVariantRow function is no longer needed since variants are fixed by category


function openCategoryModal(id = null, name = '', order = 0, allowedVariants = [], showMeasurements = 1, type = 'menu', activeDays = []) {
    document.getElementById('categoryForm').reset();
    document.getElementById('catType').value = type;

    if (btnDeleteCategory) {
        btnDeleteCategory.style.display = id ? 'block' : 'none';
    }
    
    // Toggle Active Days Container based on type
    const daysContainer = document.getElementById('catActiveDaysContainer');
    if (type === 'special') {
        daysContainer.style.display = 'block';
        // Populate active days
        document.querySelectorAll('input[name="active_days[]"]').forEach(cb => {
            cb.checked = activeDays.includes(cb.value);
        });
    } else {
        daysContainer.style.display = 'none';
    }

    // Populate Size Checkboxes (from global SIZE_DEFINITIONS)
    const variantsContainer = document.getElementById('catVariantsContainer');
    variantsContainer.innerHTML = '';
    
    if (typeof SIZE_DEFINITIONS !== 'undefined' && SIZE_DEFINITIONS.length > 0) {
        SIZE_DEFINITIONS.forEach(def => {
            // Hide "Standard" from selectable options - it's implicit for single-price items
            if (def.name === 'Standard') return;

            const isChecked = allowedVariants && allowedVariants.some(v => v == def.id) ? 'checked' : '';
            const safeName = escapeHtml(def.name);
            const safeMeasurement = escapeHtml(def.measurement);
            const div = document.createElement('div');
            div.className = 'form-check form-check-inline';
            div.innerHTML = `
                <input class="form-check-input" type="checkbox" name="allowed_variants[]" value="${def.id}" id="size_${def.id}" ${isChecked}>
                <label class="form-check-label" for="size_${def.id}">
                    ${safeName} <small class="text-muted">(${safeMeasurement})</small>
                </label>
            `;
            variantsContainer.appendChild(div);
        });
    } else {
        variantsContainer.innerHTML = '<small class="text-muted">No size definitions found.</small>';
    }

    if (id) {
        document.getElementById('categoryModalLabel').innerText = type === 'special' ? 'Edit Special Category' : 'Edit Category';
        document.getElementById('catId').value = id;
        document.getElementById('catName').value = name;
        document.getElementById('catOrder').value = order;
        document.getElementById('catShowMeasurements').checked = showMeasurements == 1;
    } else {
        document.getElementById('categoryModalLabel').innerText = type === 'special' ? 'Add Special Category' : 'Add Category';
        document.getElementById('catId').value = '';
        document.getElementById('catShowMeasurements').checked = true;
    }
    categoryModal.show();
}

async function deleteCategory() {
    const id = document.getElementById('catId').value;
    const name = document.getElementById('catName').value;
    const type = document.getElementById('catType').value;

    if (!id) {
        showToast('No category selected.', 'danger', 'Delete Failed');
        return;
    }

    const label = type === 'special' ? 'Special Category' : 'Category';
    const ok = await confirmDialog(
        `Delete this ${label} and ALL items inside it? This cannot be undone.\n\n${String(name || '').trim()}`,
        `Delete ${label}`,
        'Delete',
        'Cancel'
    );
    if (!ok) return;

    const formData = new FormData();
    formData.append('id', id);

    try {
        const res = await fetch('api/delete_category.php', { method: 'POST', body: formData, headers: withCsrfHeaders() });
        const text = await res.text();
        let data = null;
        try { data = JSON.parse(text); } catch (e) { data = null; }

        if (res.ok && data && data.success) {
            saveCurrentTabState();
            categoryModal.hide();
            showToast('Category deleted successfully.', 'success', 'Deleted');
            setTimeout(() => location.reload(), 450);
            return;
        }

        if (data && data.error) {
            showToast(data.error, 'danger', 'Delete Failed');
            return;
        }

        showToast('Error deleting category.', 'danger', 'Delete Failed');
    } catch (e) {
        console.error(e);
        showToast('Error deleting category.', 'danger', 'Delete Failed');
    }
}

async function saveItem(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    // Collect variants
    // Collect variants
    const variants = [];
    document.querySelectorAll('.variant-row').forEach(row => {
        const sizeInput = row.querySelector('input[name="variant_size_id[]"]');
        const priceInput = row.querySelector('input[name="variant_price[]"]');
        
        const size = sizeInput.value;
        const price = priceInput.value;
        
        // Only add if price is set and not zero/empty
        if (size && price && parseFloat(price) > 0) {
            variants.push({ size, price });
        }
    });

    formData.append('variants', JSON.stringify(variants));
    
    try {
        const response = await fetch('api/save_item.php', { method: 'POST', body: formData, headers: withCsrfHeaders() });
        const res = await response.json(); // Parse JSON to check for logic errors
        if (response.ok && res.success) {
            saveCurrentTabState(); // Ensure state is saved before reload
            showToast('Saved successfully.', 'success', 'Item Saved');
            setTimeout(() => location.reload(), 400);
        } else {
            showToast('Error saving item: ' + (res.error || 'Unknown error'), 'danger', 'Save Failed');
        }
    } catch (e) {
        console.error(e);
        showToast('Error saving item.', 'danger', 'Save Failed');
    }
}

async function saveCategory(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    try {
        const res = await fetch('api/save_category.php', { method: 'POST', body: formData, headers: withCsrfHeaders() });
        const text = await res.text();
        let data = null;
        try { data = JSON.parse(text); } catch (e) { data = null; }

        if (res.ok) {
            saveCurrentTabState(); // Ensure state is saved before reload
            showToast('Saved successfully.', 'success', 'Category Saved');
            setTimeout(() => location.reload(), 400);
            return;
        }

        if (data && data.error) {
            showToast(data.error, 'danger', 'Save Failed');
            return;
        }

        showToast('Error saving category.', 'danger', 'Save Failed');
    } catch(e) { console.error(e); }
}

async function deleteItem() {
    const ok = await confirmDialog('Are you sure you want to delete this item?', 'Delete Item', 'Delete', 'Cancel');
    if (!ok) return;
    
    const id = document.getElementById('itemId').value;
    const formData = new FormData();
    formData.append('id', id);
    
    try {
        const res = await fetch('api/delete_item.php', { method: 'POST', body: formData, headers: withCsrfHeaders() });
        if (res.ok) {
            saveCurrentTabState();
            showToast('Deleted successfully.', 'success', 'Item Deleted');
            setTimeout(() => location.reload(), 400);
        } else {
            showToast('Error deleting item.', 'danger', 'Delete Failed');
        }
    } catch(e) { console.error(e); }
}

// Old Special Modal Functions Removed (Refactored to use Categories)

async function saveSettings(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    try {
        const res = await fetch('api/save_settings.php', { method: 'POST', body: formData, headers: withCsrfHeaders() });
        const text = await res.text();
        let data = null;
        try { data = JSON.parse(text); } catch (e) { data = null; }

        if (res.ok) {
            showToast('Settings saved successfully.', 'success', 'Saved');
            setTimeout(() => location.reload(), 500);
            return;
        }

        if (data && data.error) {
            showToast('Error saving settings (' + res.status + '): ' + data.error, 'danger', 'Save Failed');
            return;
        }

        showToast('Error saving settings (' + res.status + ').', 'danger', 'Save Failed');
    } catch(e) { console.error(e); }
}

async function updateMenuPdf(event) {
    if (event) event.preventDefault();
    const ok = await confirmDialog('Generate and save a new Menu PDF now?', 'Update PDF', 'Generate', 'Cancel');
    if (!ok) return;
    try {
        const res = await fetch('api/generate_menu_pdf.php', { method: 'POST', headers: withCsrfHeaders() });
        const text = await res.text();
        let data = null;
        try { data = JSON.parse(text); } catch (e) { data = null; }

        if (res.ok && data && data.success) {
            showToast('PDF updated successfully.', 'success', 'PDF Updated');
            return;
        }

        if (data && data.error) {
            showToast('Error updating PDF (' + res.status + '): ' + data.error, 'danger', 'Update Failed');
            return;
        }

        showToast('Error updating PDF (' + res.status + ').', 'danger', 'Update Failed');
    } catch (e) {
        console.error(e);
        showToast('Error updating PDF.', 'danger', 'Update Failed');
    }
}

// Initialize Size Datalist
if (typeof SIZE_DEFINITIONS !== 'undefined') {
    const dl = document.createElement('datalist');
    dl.id = 'sizeOptions';
    SIZE_DEFINITIONS.forEach(def => {
        const opt = document.createElement('option');
        opt.value = def.name; 
        dl.appendChild(opt);
    });
    document.body.appendChild(dl);
}

// ------------------------------------------
// Tab State Persistence
// ------------------------------------------
// Helper to save current state explicitly
function saveCurrentTabState() {
    const activeTab = document.querySelector('.nav-link.active');
    if (activeTab) {
        const targetId = activeTab.getAttribute('data-bs-target');
        localStorage.setItem('activeDashboardTab', targetId);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Restore active tab if saved
    const activeTabId = localStorage.getItem('activeDashboardTab');
    if (activeTabId) {
        const triggerEl = document.querySelector(`button[data-bs-target="${activeTabId}"]`);
        if (triggerEl) {
            const tabInstance = new bootstrap.Tab(triggerEl);
            tabInstance.show();
        }
    }

    // 2. Listen for tab changes and save
    const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabEls.forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', function (event) {
            saveCurrentTabState();
        });
    });
});
