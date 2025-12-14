<?php 
require_once 'config/db.php';
require_once 'includes/functions.php';
$menu = get_full_menu($pdo, 'menu', true); 
$allSizes = get_size_definitions($pdo);

$seoBaseUrl = 'https://cangopizza.co.za';
$pageTitle = 'Menu | Cango Woodfired Pizza (Oudtshoorn)';
$pageDescription = 'Browse the Cango Woodfired Pizza menu in Oudtshoorn: wood-fired pizzas, grills, pasta and more. Download the menu PDF.';
$canonicalPath = '/menu.php';
$ogImagePath = '/assets/images/pizza.jpg';
$ogType = 'website';
$activeNav = 'menu';
require 'includes/header.php'; 
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="display-4 text-center text-md-start">OUR MENU</h1>
        <div class="d-flex gap-2">
            <a href="assets/menu.pdf?v=<?= is_file('assets/menu.pdf') ? filemtime('assets/menu.pdf') : time() ?>" target="_blank" class="btn btn-outline-dark"><i class="fas fa-file-pdf"></i> Download PDF</a>
        </div>
    </div>

    <div class="masonry-grid">
        <?php foreach ($menu as $category): ?>
            <?php if (!empty($category['items'])): ?>
                <?php 
                // Simple slug generation
                $anchor = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $category['name']));
                if (strpos($anchor, 'pizza') !== false) $anchor = 'pizza';
                elseif (strpos($anchor, 'grill') !== false) $anchor = 'grills';
                elseif (strpos($anchor, 'pasta') !== false) $anchor = 'pasta';
                elseif (strpos($anchor, 'dessert') !== false) $anchor = 'dessert';

                // Collect columns based on configuration OR fallback
                $columns = [];
                $has_config = !empty($category['allowed_variants']);
                
                if ($has_config) {
                    // Use configured sizes
                    foreach ($category['allowed_variants'] as $size_id) {
                        // Find definition from global 'allSizes'
                        foreach ($allSizes as $def) {
                            if ($def['id'] == $size_id) {
                                $columns[] = $def;
                                break;
                            }
                        }
                    }
                } else {
                    // Fallback: Scan items for unique size IDs
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
                    // Resolve IDs to Definitions
                    foreach ($unique_ids as $sid) {
                        foreach ($allSizes as $def) {
                            if ($def['id'] == $sid) {
                                $columns[] = $def;
                                break;
                            }
                        }
                    }
                }
                ?>
                <div class="masonry-item mb-5" id="<?= $anchor ?>">
                   <div class="menu-section h-100">
                        <div class="table-responsive">
                            <table class="table table-hover menu-table">
                                <thead class="table-light">
                                    <tr>
                                        <!-- Styled section header: Red, Larger (fs-3), Uppercase -->
                                        <th scope="col" class="w-50 text-danger fs-3 text-uppercase align-middle border-bottom-0 pt-3"><?= h($category['name']) ?></th> <!-- Section Name on Left -->
                                        <?php if (empty($columns)): ?>
                                            <th scope="col" class="text-end border-bottom-0">Price</th>
                                        <?php else: ?>
                                            <?php foreach ($columns as $col): ?>
                                                <th scope="col" class="text-center border-bottom-0">
                                                    <?php $colLabel = (count($columns) === 1 && strtolower((string)$col['name']) === 'standard') ? 'Price' : $col['name']; ?>
                                                    <?= h($colLabel) ?>
                                                    <?php 
                                                    // Check if measurements should be displayed
                                                    // Default to true if not set (backward compat)
                                                    $show_meas = $category['show_measurements'] ?? 1;
                                                    if($show_meas && $colLabel !== 'Price' && !empty($col['measurement'])): 
                                                    ?>
                                                        <br><small class="text-muted fw-normal"><?= h($col['measurement']) ?></small>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category['items'] as $item): ?>
                                        <?php $is_out_of_stock = ((int)($item['is_active'] ?? 1) !== 1); ?>
                                        <tr class="<?= $is_out_of_stock ? 'out-of-stock' : '' ?>">
                                            <td>
                                                <?php if ($is_out_of_stock): ?>
                                                    <div class="oos-mask"></div>
                                                    <div class="oos-label">Out of Stock</div>
                                                <?php endif; ?>
                                                <div class="fw-bold fs-5">
                                                    <?= h($item['name']) ?>
                                                    <?php if ($item['is_special']): ?>
                                                        <small class="text-warning ms-1"><i class="fas fa-star"></i></small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($item['description'])): ?>
                                                    <div class="text-muted small fst-italic"><?= h($item['description']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <?php if (empty($columns)): ?>
                                                <!-- No columns defined (or fallback found nothing) -> Single Price -->
                                                 <td class="text-end text-nowrap fw-bold fs-6 align-middle">
                                                    <?php if ($is_out_of_stock): ?><div class="oos-mask"></div><?php endif; ?>
                                                    <?php if (!empty($item['variants'])): ?>
                                                        <?php if (count($item['variants']) === 1): ?>
                                                            R<?= h(format_rand($item['variants'][0]['price'])) ?>
                                                        <?php else: ?>
                                                            R<?= h(format_rand($item['min_price'])) ?>+
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                 </td>
                                            <?php else: ?>
                                                <?php foreach ($columns as $col): ?>
                                                    <td class="text-center text-nowrap fw-bold fs-6 align-middle">
                                                        <?php if ($is_out_of_stock): ?><div class="oos-mask"></div><?php endif; ?>
                                                        <?php 
                                                            // Find price matching this column
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
    </div>
</div>

<style>
/* Masonry Layout */
@media (min-width: 992px) {
    .masonry-grid {
        column-count: 2;
        column-gap: 2rem;
    }
}
.masonry-item {
    break-inside: avoid; /* Prevent split across columns */
    display: inline-block; /* Fix for some browsers to respect break-inside */
    width: 100%;
}

.out-of-stock td { position: relative; }
.out-of-stock .oos-mask { position: absolute; inset: 0; background: rgba(255, 255, 255, 0.72); z-index: 2; }
.out-of-stock .oos-label { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #b71c1c; font-size: 0.9rem; z-index: 3; }

.last-no-border:last-child { border-bottom: none !important; margin-bottom: 0 !important; }
</style>

<?php require 'includes/footer.php'; ?>
