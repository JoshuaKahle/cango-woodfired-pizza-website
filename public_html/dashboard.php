<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

require_login();
$menu = get_full_menu($pdo, 'menu', true);
$sizes = get_size_definitions($pdo);

$pdf_page_width_mm = get_setting($pdo, 'pdf_page_width_mm', '148');
$pdf_page_height_mm = get_setting($pdo, 'pdf_page_height_mm', '210');
?>
<script>
    const SIZE_DEFINITIONS = <?= json_encode($sizes) ?>;
    const CSRF_TOKEN = <?= json_encode(get_csrf_token()) ?>;
</script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Manager - Cango Pizza</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-app">

<nav class="navbar navbar-dark bg-dark mb-4 admin-navbar">
    <div class="container">
        <div class="d-flex flex-column">
            <span class="navbar-brand mb-0">Cango Woodfired Pizza</span>
            <span class="admin-subtitle">Management Dashboard</span>
        </div>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>


    <!-- Dashboard Tabs -->
    <ul class="nav nav-tabs mb-4 admin-tabs" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="menu-tab-link" data-bs-toggle="tab" data-bs-target="#menu-tab" type="button" role="tab">
                <i class="fas fa-utensils me-2"></i>Edit Menu
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="specials-tab-link" data-bs-toggle="tab" data-bs-target="#specials-tab" type="button" role="tab">
                <i class="fas fa-tags me-2"></i>Edit Specials
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="settings-tab-link" data-bs-toggle="tab" data-bs-target="#settings-tab" type="button" role="tab">
                <i class="fas fa-sliders-h me-2"></i>Settings
            </button>
        </li>
    </ul>

    <div class="container px-3 px-lg-5">
        <div class="tab-content" id="dashboardTabContent">
            
            <!-- ================= EDIT MENU TAB ================= -->
            <div class="tab-pane fade show active" id="menu-tab" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Menu Categories</h4>
                    <div class="d-flex gap-2">
                        <a href="assets/menu.pdf?v=<?= is_file('assets/menu.pdf') ? filemtime('assets/menu.pdf') : time() ?>" target="_blank" class="btn btn-outline-dark">
                            <i class="fas fa-file-pdf"></i> Download Menu PDF
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateMenuPdf(event)">Update PDF</button>
                        <button class="btn btn-success" onclick="openCategoryModal()">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                </div>

                <div id="menu-container">
                    <?php foreach ($menu as $category): ?>
                        <div class="category-block card mb-4" data-cat-id="<?= $category['id'] ?>">
                            <div class="card-header d-flex justify-content-between align-items-center bg-secondary text-white">
                                <h4 class="m-0 cursor-pointer" onclick='openCategoryModal(<?= $category['id'] ?>, <?= json_encode($category['name'], JSON_HEX_APOS) ?>, <?= $category['display_order'] ?>, <?= json_encode($category['allowed_variants'] ?? []) ?>, <?= $category['show_measurements'] ?? 1 ?>, "menu")'>
                                    <?= h($category['name']) ?> <small><i class="fas fa-edit fa-xs"></i></small>
                                </h4>
                                <button class="btn btn-light btn-sm" onclick='openItemModal(<?= $category['id'] ?>, null, <?= json_encode($category['allowed_variants'] ?? []) ?>, "menu")'>
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($category['items'] as $item): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card h-100 menu-item-card <?= $item['is_active'] ? '' : 'border-danger opacity-75' ?>" onclick='openItemModal(<?= $category['id'] ?>, <?= json_encode($item, JSON_HEX_APOS) ?>, <?= json_encode($category['allowed_variants'] ?? []) ?>, "menu")'>
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between">
                                                        <h5 class="card-title"><?= h($item['name']) ?></h5>
                                                        <span class="text-primary fw-bold">
                                                            <?php if (count($item['variants']) > 1): ?>
                                                                R<?= h($item['min_price']) ?> - R<?= h(max(array_column($item['variants'], 'price'))) ?>
                                                            <?php elseif (!empty($item['variants'])): ?>
                                                                R<?= h($item['variants'][0]['price']) ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">No Price</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <p class="card-text small text-muted"><?= h($item['description']) ?></p>
                                                    
                                                    <div class="mt-2">
                                                        <?php if (!$item['is_active']): ?>
                                                            <span class="badge bg-danger">Out of Stock</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($category['items'])): ?>
                                        <div class="col-12 text-center text-muted p-3">No items yet. Add one!</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ================= EDIT SPECIALS TAB ================= -->
            <div class="tab-pane fade" id="specials-tab" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Specials Categories</h4>
                    <div class="d-flex gap-2">
                        <a href="assets/menu.pdf?v=<?= is_file('assets/menu.pdf') ? filemtime('assets/menu.pdf') : time() ?>" target="_blank" class="btn btn-outline-dark">
                            <i class="fas fa-file-pdf"></i> Download Menu PDF
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateMenuPdf(event)">Update PDF</button>
                        <button class="btn btn-primary" onclick="openCategoryModal(null, '', 0, [], 1, 'special')">
                            <i class="fas fa-plus"></i> Add Special Category
                        </button>
                    </div>
                </div>
                
                <div id="specials-container">
                    <?php 
                    $specialCategories = get_full_menu($pdo, 'special', true);
                    // We can reuse the same loop structure, logic is identical, just data source differs
                    ?>
                    <?php foreach ($specialCategories as $category): ?>
                        <div class="category-block card mb-4 border-primary" data-cat-id="<?= $category['id'] ?>">
                            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white bg-opacity-75">
                                <h4 class="m-0 cursor-pointer" onclick='openCategoryModal(<?= $category['id'] ?>, <?= json_encode($category['name'], JSON_HEX_APOS) ?>, <?= $category['display_order'] ?>, <?= json_encode($category['allowed_variants'] ?? []) ?>, <?= $category['show_measurements'] ?? 1 ?>, "special", <?= json_encode($category['active_days'] ?? [], JSON_HEX_APOS) ?>)'>
                                    <?= h($category['name']) ?> <small><i class="fas fa-edit fa-xs"></i></small>
                                </h4>
                                <button class="btn btn-light btn-sm" onclick='openItemModal(<?= $category['id'] ?>, null, <?= json_encode($category['allowed_variants'] ?? []) ?>, "special")'>
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-2">
                                    <strong>Active Days:</strong> 
                                    <?= !empty($category['active_days']) ? h(implode(', ', $category['active_days'])) : 'Every Day' ?>
                                </p>
                                <div class="row">
                                    <?php foreach ($category['items'] as $item): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card h-100 menu-item-card <?= $item['is_active'] ? '' : 'border-danger opacity-75' ?>" onclick='openItemModal(<?= $category['id'] ?>, <?= json_encode($item, JSON_HEX_APOS) ?>, <?= json_encode($category['allowed_variants'] ?? []) ?>, "special")'>
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between">
                                                        <h5 class="card-title"><?= h($item['name']) ?></h5>
                                                        <span class="text-primary fw-bold">
                                                            <?php if (count($item['variants']) > 1): ?>
                                                                R<?= h($item['min_price']) ?> - R<?= h(max(array_column($item['variants'], 'price'))) ?>
                                                            <?php elseif (!empty($item['variants'])): ?>
                                                                R<?= h($item['variants'][0]['price']) ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">No Price</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <p class="card-text small text-muted"><?= h($item['description']) ?></p>
                                                    
                                                    <div class="mt-2">
                                                        <?php if (!$item['is_active']): ?>
                                                            <span class="badge bg-danger">Inactive</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($category['items'])): ?>
                                        <div class="col-12 text-center text-muted p-3">No specials in this category yet.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($specialCategories)): ?>
                        <div class="col-12 text-center text-muted py-5">
                            <p>No special categories defined yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ================= SETTINGS TAB ================= -->
            <div class="tab-pane fade" id="settings-tab" role="tabpanel">
                <form id="settingsForm" onsubmit="saveSettings(event)">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="card table-card h-100">
                                <div class="card-header bg-light d-flex align-items-center justify-content-between">
                                    <h5 class="m-0"><i class="fas fa-ruler-combined me-2"></i>Pizza Sizes</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3">Set the measurements shown on the public menu (e.g. 23cm).</p>
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 35%">Size Name</th>
                                                    <th>Measurement</th>
                                                    <th style="width: 70px">ID</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sizes as $size): ?>
                                                    <?php if ($size['name'] === 'Standard') continue; // Skip Standard ?>
                                                    <tr>
                                                        <td class="fw-bold"><?= h($size['name']) ?></td>
                                                        <td>
                                                            <input type="text" class="form-control" name="measurements[<?= $size['id'] ?>]" value="<?= h($size['measurement']) ?>" placeholder="e.g. 30cm">
                                                        </td>
                                                        <td class="text-muted small"><?= $size['id'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="card table-card h-100">
                                <div class="card-header bg-light d-flex align-items-center justify-content-between">
                                    <h5 class="m-0"><i class="fas fa-file-export me-2"></i>Menu Export</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3">Control PDF page size and generate/download the latest menu export.</p>

                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="form-label">PDF Width (mm)</label>
                                            <input type="number" step="1" min="1" class="form-control" name="pdf_page_width_mm" value="<?= h($pdf_page_width_mm) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">PDF Height (mm)</label>
                                            <input type="number" step="1" min="1" class="form-control" name="pdf_page_height_mm" value="<?= h($pdf_page_height_mm) ?>">
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
                                        <a href="assets/menu.pdf?v=<?= is_file('assets/menu.pdf') ? filemtime('assets/menu.pdf') : time() ?>" target="_blank" class="btn btn-outline-dark">
                                            <i class="fas fa-file-pdf"></i> Download Menu PDF
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary" onclick="updateMenuPdf(event)">Update PDF</button>
                                        <button type="submit" class="btn btn-primary">Save Settings</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div> <!-- End Tab Content -->
    </div> <!-- End Container -->
</div>

<!-- Item Modal -->
<div class="modal fade admin-modal" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="itemForm" onsubmit="saveItem(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalLabel">Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="itemId">
                    <input type="hidden" name="category_id" id="itemCategoryId">
                    
                    <div class="mb-3">
                        <label class="form-label" for="itemNameInput" id="itemNameLabel">Item Name</label>
                        <!-- Standard Menu Item Input -->
                        <input type="text" name="name" id="itemNameInput" class="form-control" required>
                        <!-- Special Item Textarea (Hidden by default) -->
                        <textarea name="name" id="itemNameTextarea" class="form-control" rows="3" disabled style="display:none;" placeholder="Enter special details here..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sizes & Prices</label>
                        <div id="variants-container">
                            <!-- Variants injected by JS based on Category Allowed Sizes -->
                        </div>
                        <small class="text-muted d-block mt-1">Leave price empty for sizes not available.</small>
                    </div>
                    <div class="mb-3" id="itemDescriptionContainer">
                        <label class="form-label" for="itemDescription">Description</label>
                        <textarea name="description" id="itemDescription" class="form-control" rows="2"></textarea>
                    </div>
                    <!-- Image upload removed -->
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="itemIsActive" checked>
                                <label class="form-check-label" for="itemIsActive">In Stock</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="btnDelete" onclick="deleteItem()" style="display:none;">Delete</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade admin-modal" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="categoryForm" onsubmit="saveCategory(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="catId">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" id="catName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" id="catOrder" class="form-control" value="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label d-block">Allowed Size Variants</label>
                        <div id="catVariantsContainer">
                            <!-- Injected via JS based on SIZE_DEFINITIONS -->
                        </div>
                        <small class="text-muted">Select which sizes (columns) to display for this section.</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_measurements" id="catShowMeasurements" checked>
                            <label class="form-check-label" for="catShowMeasurements">Display Measurements? (e.g. 30cm)</label>
                        </div>
                    </div>

                    <!-- Hidden Type Field -->
                    <input type="hidden" name="type" id="catType" value="menu">

                    <!-- Active Days (Only for Specials) -->
                    <div class="mb-3" id="catActiveDaysContainer" style="display:none;">
                        <label class="form-label d-block">Active Days</label>
                        <div class="btn-group w-100" role="group">
                            <?php $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; ?>
                            <?php foreach($days as $day): ?>
                                <input type="checkbox" class="btn-check" name="active_days[]" id="cat_day_<?= $day ?>" value="<?= $day ?>" autocomplete="off">
                                <label class="btn btn-outline-secondary" for="cat_day_<?= $day ?>"><?= $day ?></label>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Leave empty for "Every Day".</small>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="btnDeleteCategory" onclick="deleteCategory()" style="display:none;">Delete</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Category</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Special Modal (NEW) -->
<div class="modal fade admin-modal" id="specialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="specialForm" onsubmit="saveSpecial(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="specialModalLabel">Edit Special</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="specialId">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="specialTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="specialDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">Active Days</label>
                        <div class="btn-group w-100" role="group">
                            <?php $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; ?>
                            <?php foreach($days as $day): ?>
                                <input type="checkbox" class="btn-check" name="active_days[]" id="day_<?= $day ?>" value="<?= $day ?>" autocomplete="off">
                                <label class="btn btn-outline-secondary" for="day_<?= $day ?>"><?= $day ?></label>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Leave empty for "Every Day".</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="specialIsActive" checked>
                            <label class="form-check-label" for="specialIsActive">Active (Visible)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="btnDeleteSpecial" onclick="deleteSpecial()" style="display:none;">Delete</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Special</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="adminToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div id="adminToastHeader" class="toast-header">
            <strong class="me-auto" id="adminToastTitle">Notice</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="adminToastBody"></div>
    </div>
</div>

<div class="modal fade admin-modal" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Please Confirm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">Are you sure?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="confirmModalCancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmModalOk">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/admin_visual_editor.js?v=<?= time() ?>"></script>
</body>
</html>
