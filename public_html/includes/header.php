<?php
 $seoBaseUrl = isset($seoBaseUrl) && is_string($seoBaseUrl) && $seoBaseUrl !== '' ? rtrim($seoBaseUrl, '/') : 'https://cangopizza.co.za';
 $pageTitle = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== '' ? $pageTitle : 'Cango Woodfired Pizza';
 $pageDescription = isset($pageDescription) && is_string($pageDescription) && $pageDescription !== '' ? $pageDescription : 'Wood-fired pizzas, grills, pasta and takeaway specials in Oudtshoorn.';
 $canonicalPath = isset($canonicalPath) && is_string($canonicalPath) && $canonicalPath !== '' ? $canonicalPath : '/index.php';
 if ($canonicalPath[0] !== '/') {
     $canonicalPath = '/' . $canonicalPath;
 }
 $canonicalUrl = $seoBaseUrl . $canonicalPath;
 
 $ogImagePath = isset($ogImagePath) && is_string($ogImagePath) && $ogImagePath !== '' ? $ogImagePath : '/assets/images/woodfired_pizza_hero.jpg';
 if ($ogImagePath[0] !== '/') {
     $ogImagePath = '/' . $ogImagePath;
 }
 $ogImageUrl = $seoBaseUrl . $ogImagePath;
 $ogType = isset($ogType) && is_string($ogType) && $ogType !== '' ? $ogType : 'website';
 
 $activeNav = isset($activeNav) && is_string($activeNav) ? $activeNav : '';
 $navHomeClass = ($activeNav === 'home') ? 'text-warning text-decoration-none fw-bold' : 'text-white text-decoration-none';
 $navMenuClass = ($activeNav === 'menu') ? 'text-warning text-decoration-none fw-bold' : 'text-white text-decoration-none';
 $navSpecialsClass = ($activeNav === 'specials') ? 'text-warning text-decoration-none fw-bold' : 'text-white text-decoration-none';
 
 $jsonLd = [
     '@context' => 'https://schema.org',
     '@type' => 'Restaurant',
     'name' => 'Cango Woodfired Pizza',
     'url' => $seoBaseUrl . '/index.php',
     'image' => $ogImageUrl,
     'telephone' => '+27442720708',
     'servesCuisine' => ['Pizza', 'Grill', 'Pasta'],
     'priceRange' => 'R',
     'address' => [
         '@type' => 'PostalAddress',
         'streetAddress' => 'Langenhoven Str. 187',
         'addressLocality' => 'Oudtshoorn',
         'addressCountry' => 'ZA',
     ],
     'openingHoursSpecification' => [
         [
             '@type' => 'OpeningHoursSpecification',
             'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
             'opens' => '10:00',
             'closes' => '21:00',
         ],
     ],
     'menu' => $seoBaseUrl . '/menu.php',
 ];
 ?>
 <!DOCTYPE html>
 <html lang="en-ZA">
 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
     <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
     <meta name="robots" content="index,follow">
     <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
 
     <meta property="og:site_name" content="Cango Woodfired Pizza">
     <meta property="og:type" content="<?= htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8') ?>">
     <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
     <meta property="og:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
     <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
     <meta property="og:image" content="<?= htmlspecialchars($ogImageUrl, ENT_QUOTES, 'UTF-8') ?>">
     <meta property="og:locale" content="en_ZA">
 
     <meta name="twitter:card" content="summary_large_image">
     <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
     <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
     <meta name="twitter:image" content="<?= htmlspecialchars($ogImageUrl, ENT_QUOTES, 'UTF-8') ?>">
 
     <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
 
     <link rel="icon" type="image/png" href="assets/images/favicon.png">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
     <link href="assets/css/style.css" rel="stylesheet">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <link rel="preconnect" href="https://fonts.googleapis.com">
     <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
     <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&family=Oswald:wght@400;700&display=swap" rel="stylesheet">
 </head>
 <body>
 
 <header class="main-header text-white sticky-top">
     <div class="container d-flex justify-content-between align-items-center py-3">
         <a href="index.php" class="logo-wrapper mb-0">
             <img src="assets/images/logo.png" alt="Cango Pizza" class="header-logo">
         </a>
         <nav class="d-none d-md-flex gap-4" aria-label="Primary">
             <a href="index.php" class="<?= $navHomeClass ?>">HOME</a>
             <a href="menu.php" class="<?= $navMenuClass ?>">MENU</a>
             <a href="specials.php" class="<?= $navSpecialsClass ?>">SPECIALS</a>
         </nav>
         <a href="tel:0442720708" class="btn btn-warning fw-bold d-none d-sm-block">ORDER: 044 272 0708 / 9</a>
         <button class="btn btn-outline-light d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-expanded="false" aria-label="Toggle navigation">
             <i class="fas fa-bars"></i>
         </button>
     </div>
     <div class="collapse bg-dark border-top border-secondary p-3" id="mobileNav">
         <div class="d-flex flex-column gap-3">
             <a href="index.php" class="<?= $navHomeClass ?>">HOME</a>
             <a href="menu.php" class="<?= $navMenuClass ?>">MENU</a>
             <a href="specials.php" class="<?= $navSpecialsClass ?>">SPECIALS</a>
             <a href="tel:0442720708" class="btn btn-warning w-100">ORDER: 044 272 0708 / 9</a>
         </div>
     </div>
 </header>
 
 <div class="info-bar text-center">
     <div class="container d-flex flex-wrap justify-content-center justify-content-md-between px-4">
         <span><i class="fas fa-clock"></i> MON - SAT: 10AM - 9PM | SUN: CLOSED</span>
         <a class="js-map-link text-white text-decoration-none" data-map-query="Langenhoven Str. 187 Oudtshoorn" href="https://www.google.com/maps/search/?api=1&query=Langenhoven%20Str.%20187%20Oudtshoorn" target="_blank" rel="noopener"><i class="fas fa-map-marker-alt"></i> Langenhoven Str. 187 Oudtshoorn</a>
         <a href="tel:0442720708" class="text-white text-decoration-none d-md-none"><i class="fas fa-phone"></i> CALL NOW</a>
     </div>
 </div>
 
 <main id="main-content">
