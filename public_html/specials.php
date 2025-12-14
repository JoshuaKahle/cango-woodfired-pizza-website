<?php 
require_once 'config/db.php';
require_once 'includes/functions.php';

$filter = $_GET['filter'] ?? 'all';

// Fetch specials using the new Category system
$specialsCategories = get_full_menu($pdo, 'special');
$allSizes = get_size_definitions($pdo);

// Filter Logic
$today = date('D');
$filteredCategories = [];

foreach ($specialsCategories as $cat) {
    // Active Days Logic
    if (!empty($cat['active_days']) && !in_array($today, $cat['active_days'])) {
        if ($filter === 'today') continue;
    }
    // If filtering by "today", also check if the category even HAS active days set (if typical logic applies)
    // Assuming empty active_days means "Every Day"
    
    $filteredCategories[] = $cat;
}

$seoBaseUrl = 'https://cangopizza.co.za';
$pageTitle = 'Specials | Cango Woodfired Pizza (Oudtshoorn)';
$pageDescription = 'View today\'s specials and weekly deals from Cango Woodfired Pizza in Oudtshoorn. Call 044 272 0708 / 9 to order.';
$canonicalPath = '/specials.php';
$ogImagePath = '/assets/images/woodfired_pizza_hero.jpg';
$ogType = 'website';
$activeNav = 'specials';
require 'includes/header.php'; 
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap">
        <h1 class="display-4 text-center text-md-start text-special">SPECIALS</h1>
        <div class="btn-group">
            <a href="specials.php?filter=all" class="btn <?= $filter === 'all' ? 'btn-warning' : 'btn-outline-dark' ?>">All Specials</a>
            <a href="specials.php?filter=today" class="btn <?= $filter === 'today' ? 'btn-warning' : 'btn-outline-dark' ?>">Today's Specials</a>
        </div>
    </div>

    <div class="masonry-grid">
        <?php foreach ($filteredCategories as $category): ?>
            <?php if (!empty($category['items'])): ?>
                <?php 
                // Collect columns based on configuration OR fallback (Same logic as menu.php)
                $columns = [];
                $has_config = !empty($category['allowed_variants']);
                
                if ($has_config) {
                    foreach ($category['allowed_variants'] as $size_id) {
                        foreach ($allSizes as $def) {
                            if ($def['id'] == $size_id) {
                                $columns[] = $def;
                                break;
                            }
                        }
                    }
                } else {
                    $unique_ids = [];
                     foreach ($category['items'] as $item) {
                         if (!empty($item['variants'])) {
                            foreach ($item['variants'] as $v) {
                                if (!in_array($v['size_id'], $unique_ids)) {
                                    $unique_ids[] = $v['size_id'];
                                }
                            }
                         }
                    }
                    foreach ($unique_ids as $sid) {
                        foreach ($allSizes as $def) {
                            if ($def['id'] == $sid) {
                                $columns[] = $def;
                                break;
                            }
                        }
                    }
                }
                
                // Format Active Days for display
                $activeDaysLabel = 'Every Day';
                $showActiveTodayBadge = false;
                if (!empty($category['active_days'])) {
                    $activeDaysLabel = implode(', ', $category['active_days']);
                    $showActiveTodayBadge = in_array($today, $category['active_days']);
                }
                ?>
                <div class="masonry-item mb-5">
                   <div class="menu-section h-100">
                        <div class="table-responsive">
                            <table class="table table-hover menu-table specials-table">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="w-50 text-special fs-3 text-uppercase align-middle border-bottom-0 pt-3">
                                            <?= h($category['name']) ?>
                                            <div class="text-muted small fw-normal mt-1" style="font-size: 0.6em; letter-spacing: normal;">
                                                <i class="far fa-calendar-alt me-1"></i> <?= h($activeDaysLabel) ?>
                                                <?php if ($showActiveTodayBadge): ?>
                                                    <span class="badge bg-success ms-1">Active Today</span>
                                                <?php endif; ?>
                                            </div>
                                        </th>
                                        <?php if (empty($columns) || count($columns) < 2): ?>
                                            <th scope="col" class="text-end border-bottom-0">Price</th>
                                        <?php else: ?>
                                            <?php foreach ($columns as $col): ?>
                                                <th scope="col" class="text-center border-bottom-0">
                                                    <?= h($col['name']) ?>
                                                    <?php if(!empty($col['measurement'])): ?>
                                                        <br><small class="text-muted fw-normal"><?= h($col['measurement']) ?></small>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold fs-5">
                                                    <!-- Special logic: Name is the main content (Textarea) -->
                                                    <?= nl2br(h($item['name'])) ?>
                                                </div>
                                                <!-- Description hidden as requested by user in Admin, but if data exists, show it -->
                                                <?php if (!empty($item['description'])): ?>
                                                    <div class="text-muted small fst-italic mt-1"><?= h($item['description']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <?php if (empty($columns) || count($columns) < 2): ?>
                                                 <td class="text-end text-nowrap fw-bold fs-5 align-middle text-special">
                                                    <?php if (!empty($columns) && count($columns) === 1): ?>
                                                        <?php 
                                                            // Single Variant Configured - Find it
                                                            $col = $columns[0];
                                                            $price = '-';
                                                            foreach ($item['variants'] as $v) {
                                                                if ($v['size_id'] == $col['id']) {
                                                                    $price = 'R' . format_rand($v['price']);
                                                                    break;
                                                                }
                                                            }
                                                            echo h($price);
                                                        ?>
                                                    <?php elseif (!empty($item['variants'])): ?>
                                                        <?php if (count($item['variants']) === 1): ?>
                                                            R<?= h(format_rand($item['variants'][0]['price'])) ?>
                                                        <?php else: ?>
                                                            R<?= h(format_rand($item['min_price'])) ?>+
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted small">See Details</span>
                                                    <?php endif; ?>
                                                 </td>
                                            <?php else: ?>
                                                <?php foreach ($columns as $col): ?>
                                                    <td class="text-center text-nowrap fw-bold fs-6 align-middle text-special">
                                                        <?php 
                                                            $price = '-';
                                                            foreach ($item['variants'] as $v) {
                                                                if ($v['size_id'] == $col['id']) {
                                                                    $price = 'R' . format_rand($v['price']);
                                                                    break;
                                                                }
                                                            }
                                                            echo h($price);
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                   </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php if (empty($filteredCategories)): ?>
            <div class="col-12 text-center py-5">
                <h3>No specials found for this selection. Check back later!</h3>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Ensure Masonry Reuse */
.masonry-grid { column-count: 1; }
@media (min-width: 992px) {
    .masonry-grid { column-count: 2; column-gap: 2rem; }
}
.masonry-item { break-inside: avoid; display: inline-block; width: 100%; }

 .public-admin-toast .toast-header {
    background: var(--primary-color);
    color: #fff;
 }

 .public-admin-toast .toast-body {
    background: #fff;
 }
</style>

<?php require 'includes/footer.php'; ?>
