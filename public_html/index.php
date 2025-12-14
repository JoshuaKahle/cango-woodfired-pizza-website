<?php 
require_once 'config/db.php';
require_once 'includes/functions.php';
$seoBaseUrl = 'https://cangopizza.co.za';
$pageTitle = 'Cango Woodfired Pizza | Oudtshoorn Wood-Fired Pizza & Takeaways';
$pageDescription = 'Cango Woodfired Pizza in Oudtshoorn. Wood-fired pizzas, grills, pasta and takeaway specials. Call 044 272 0708 / 9.';
$canonicalPath = '/';
$ogImagePath = '/assets/images/woodfired_pizza_hero.jpg';
$ogType = 'website';
$activeNav = 'home';
require 'includes/header.php'; 
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <h1 class="display-3 fw-bold">CANGO WOODFIRED PIZZA</h1>
        <p class="lead mb-2">Woodfired Pizzas & Take Aways</p>
        <div class="badge bg-danger fs-5 mb-4 px-4 py-2">FREE Delivery for orders over R 150.00</div>
        <div class="d-flex justify-content-center gap-3">
            <a href="menu.php" class="btn btn-lg btn-warning text-dark fw-bold">VIEW MENU</a>
            <a href="specials.php" class="btn btn-lg btn-outline-light fw-bold">VIEW SPECIALS</a>
        </div>
    </div>
</div>

<!-- Today's Special Section -->
<?php
$today = date('D');
$specialsCategories = get_full_menu($pdo, 'special');
$todaysSpecials = [];

foreach ($specialsCategories as $cat) {
    // Logic: Must have active_days set AND contain today
    // "Every Day" specials (empty active_days) are EXCLUDED as per user request
    if (!empty($cat['active_days']) && in_array($today, $cat['active_days'])) {
         foreach ($cat['items'] as $item) {
            $item['category_name'] = $cat['name'];
            $todaysSpecials[] = $item;
        }
    }
}
?>

<div class="container py-5 border-bottom">
    <div class="text-center mb-4">
        <h2 class="display-5 text-special fw-bold">TODAY'S SPECIAL</h2>
        <p class="text-muted"><?= date('l') ?> Exclusive Deals</p>
    </div>

    <?php if (!empty($todaysSpecials)): ?>
        <div class="row justify-content-center">
            <?php foreach ($todaysSpecials as $item): ?>
                <div class="col-md-8 col-lg-6 mb-4">
                    <div class="card h-100 border-warning shadow-sm"> <!-- Highlighted Card -->
                        <div class="card-body text-center">
                            <span class="badge bg-danger mb-2">LIMITED TIME</span>
                            <h3 class="card-title fw-bold display-6"><?= h($item['name']) ?></h3>
                            <p class="text-muted"><?= h($item['category_name']) ?></p>
                            
                            <h2 class="text-danger fw-bold my-3">
                                <?php if (!empty($item['variants'])): ?>
                                     <?php if (count($item['variants']) === 1): ?>
                                        R<?= h(format_rand($item['variants'][0]['price'])) ?>
                                    <?php else: ?>
                                        R<?= h(format_rand($item['min_price'])) ?>+
                                    <?php endif; ?>
                                <?php endif; ?>
                            </h2>
                            
                             <?php if (!empty($item['description'])): ?>
                                <p class="card-text fst-italic"><?= h($item['description']) ?></p>
                            <?php endif; ?>

                             <?php if (count($item['variants']) > 1): ?>
                                <div class="d-inline-block bg-light p-3 rounded mt-2 text-start">
                                    <?php foreach ($item['variants'] as $v): ?>
                                        <div class="d-flex justify-content-between gap-3">
                                            <span><strong><?= h($v['size_label']) ?></strong>:</span>
                                            <span>R<?= h(format_rand($v['price'])) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <a href="specials.php" class="btn btn-warning fw-bold">View All Specials</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-4 bg-light rounded shadow-sm border">
            <h4 class="text-muted mb-3">No specific special for today (<?= date('l') ?>)</h4>
            <p class="lead mb-4">But don't worry! We have amazing <strong>Every Day Specials</strong> waiting for you.</p>
            <a href="specials.php" class="btn btn-lg btn-outline-dark">Check Out Our Deals</a>
        </div>
    <?php endif; ?>
</div>

<!-- Menu Categories -->
 <div class="container py-5">
    <div class="row g-4">
        <div class="col-md-6">
            <a href="menu.php#pizza" class="text-decoration-none">
                <div class="category-card d-flex flex-column justify-content-end p-4 rounded shadow-sm" style="background-image: url('assets/images/pizza.jpg');">
                    <div class="overlay"></div>
                    <div class="position-relative z-1 text-white">
                        <h3 class="mb-2">Woodfired Pizza</h3>
                        <p class="mb-0 card-desc">Authentic woodfired pizzas. Traditional, Gourmet, and Regular options available.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
             <a href="menu.php#grills" class="text-decoration-none">
                <div class="category-card d-flex flex-column justify-content-end p-4 rounded shadow-sm" style="background-image: url('assets/images/grilled.jpg');">
                    <div class="overlay"></div>
                    <div class="position-relative z-1 text-white">
                        <h3 class="mb-2">Tasty Grills</h3>
                        <p class="mb-0 card-desc">Delicious ribs, chicken wings, and gourmet subs loaded with flavor.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
             <a href="menu.php#pasta" class="text-decoration-none">
                <div class="category-card d-flex flex-column justify-content-end p-4 rounded shadow-sm" style="background-image: url('assets/images/pasta.jpg');">
                    <div class="overlay"></div>
                    <div class="position-relative z-1 text-white">
                        <h3 class="mb-2">Pasta & Curry</h3>
                        <p class="mb-0 card-desc">Hearty beef curry, lasagna, alfredo, and more for a comforting meal.</p>
                    </div>
                </div>
            </a>
        </div>
         <div class="col-md-6">
             <a href="menu.php#dessert" class="text-decoration-none">
                <div class="category-card d-flex flex-column justify-content-end p-4 rounded shadow-sm" style="background-image: url('assets/images/dessert.jpg');">
                    <div class="overlay"></div>
                    <div class="position-relative z-1 text-white">
                        <h3 class="mb-2">Desserts</h3>
                        <p class="mb-0 card-desc">Decadent malva pudding, brownies, and waffles to satisfy your sweet tooth.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4 align-items-stretch">
        <div class="col-lg-7">
            <div class="p-4 bg-light border rounded h-100">
                <h2 class="h3 fw-bold mb-3">Visit or Call</h2>
                <p class="mb-2">We offer wood-fired pizzas, grills, pasta and takeaway specials in Oudtshoorn.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="fw-bold">Address</div>
                        <address class="mb-0">
                            <a class="js-map-link text-decoration-none" data-map-query="Langenhoven Str. 187 Oudtshoorn" href="https://www.google.com/maps/search/?api=1&query=Langenhoven%20Str.%20187%20Oudtshoorn" target="_blank" rel="noopener">Langenhoven Str. 187, Oudtshoorn</a>
                        </address>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-bold">Phone</div>
                        <div>
                            <a href="tel:0442720708" class="text-decoration-none">044 272 0708 / 9</a>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="fw-bold">Hours</div>
                        <div>Mon - Sat: 10AM - 9PM</div>
                        <div>Sun: Closed</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="p-4 bg-dark text-white rounded h-100">
                <h2 class="h3 fw-bold mb-3">Quick Links</h2>
                <div class="d-grid gap-2">
                    <a href="menu.php" class="btn btn-warning fw-bold">View Menu</a>
                    <a href="specials.php" class="btn btn-outline-light fw-bold">View Specials</a>
                    <a href="assets/menu.pdf?v=<?= is_file('assets/menu.pdf') ? filemtime('assets/menu.pdf') : time() ?>" target="_blank" class="btn btn-outline-light">Download Menu PDF</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
