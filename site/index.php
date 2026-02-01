<?php
require_once __DIR__ . '/../system/includes/functions.php';

$currentLang = getLanguage();

// Load base data
$baseData = json_decode(file_get_contents(__DIR__ . '/../assets/js/data.json'), true);

// Load language-specific data
$langDataFile = __DIR__ . "/../assets/js/data_{$currentLang}.json";
$langData = [];
if (file_exists($langDataFile)) {
    $langData = json_decode(file_get_contents($langDataFile), true);
}

// Merge language data into base data
$appData = array_merge($baseData, $langData);

// Fetch all necessary data from database
$allBrands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
$allCountries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
$allPackSizes = db()->query("SELECT DISTINCT pack_size FROM product_packs ORDER BY pack_size ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($allPackSizes)) $allPackSizes = [1, 10, 25, 50, 100];

// Fetch products from database
$groupedProducts = getGroupedProducts();
$dbPricingData = [];

// Helper maps
$brandMap = [];
foreach ($allBrands as $b) $brandMap[$b['code']] = $b;
$countryMap = [];
foreach ($allCountries as $c) $countryMap[$c['code']] = $c;

foreach ($groupedProducts as $brandCode => $countries) {
    $brandInfo = $brandMap[$brandCode] ?? null;
    $dbPricingData[$brandCode] = [
        'name' => __("brand_{$brandCode}", $brandInfo['name'] ?? ucfirst($brandCode)),
        'logo' => $brandInfo['logo'] ?? 'assets/images/brand/default.png',
        'options' => []
    ];
    foreach ($countries as $countryCode => $products) {
        foreach ($products as $product) {
            foreach ($product['packs'] as $pack) {
                $dbPricingData[$brandCode]['options'][$countryCode][] = [
                    'denomination' => $product['denomination'],
                    'pack_size' => $pack['pack_size'],
                    'price' => $pack['price_digital'],
                    'price_digital' => $pack['price_digital'],
                    'price_physical' => $pack['price_physical'],
                    'currency' => $product['currency']
                ];
            }
        }
    }
}

$pricingData = $dbPricingData;

// Re-map country names for the UI logic
$countryNames = [];
foreach ($allCountries as $c) {
    $countryNames[$c['code']] = __("country_{$c['code']}", $c['name']) . ' (' . $c['currency'] . ')';
}

$exchangeRates = $appData['exchangeRates'];
$exchangeRates['USD'] = (float)getSetting('usd_to_aed', $exchangeRates['USD'] ?? 3.673);
$faqs = $appData['faqs'];
$testimonials = $appData['testimonials'];

// Default view for SSR
$defaultBrand = !empty($allBrands) ? $allBrands[0]['code'] : 'apple';
$defaultCountry = !empty($allCountries) ? $allCountries[0]['code'] : 'uae';

// Get available pack sizes for the default brand/country for SSR
$defaultOptionsForPacks = $pricingData[$defaultBrand]['options'][$defaultCountry] ?? [];
$ssrPackSizes = array_unique(array_map(function($opt) {
    return (int)$opt['pack_size'];
}, $defaultOptionsForPacks));
sort($ssrPackSizes);

$defaultPackSize = !empty($ssrPackSizes) ? $ssrPackSizes[0] : (!empty($allPackSizes) ? $allPackSizes[0] : 100);

$selectedBrandInfo = $brandMap[$defaultBrand] ?? null;
$selectedCountryInfo = $countryMap[$defaultCountry] ?? null;
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $currentLang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('site_title'); ?></title>
    <meta name="description" content="<?php echo __('site_description'); ?>">
    <meta name="keywords" content="<?php echo __('site_keywords'); ?>">

    <link rel="alternate" hreflang="en" href="<?php echo BASE_URL; ?>en/" />
    <link rel="alternate" hreflang="ar" href="<?php echo BASE_URL; ?>ar/" />
    <link rel="alternate" hreflang="x-default" href="<?php echo BASE_URL; ?>en/" />
    <link rel="canonical" href="<?php echo BASE_URL . $currentLang; ?>/" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="preload" as="image" href="<?php echo BASE_URL; ?>assets/images/hero-mobile.avif" media="(max-width: 600px)" fetchpriority="high">
    <link rel="preload" as="image" href="<?php echo BASE_URL; ?>assets/images/hero.avif" media="(min-width: 601px)" fetchpriority="high">
    <link rel="preload" href="<?php echo BASE_URL; ?>assets/fonts/icon/icon.woff2" as="font" type="font/woff2" crossorigin="anonymous" />
    <link rel="preload" href="<?php echo BASE_URL; ?>assets/fonts/poppins/Poppins-Medium.woff" as="font" type="font/woff" crossorigin="anonymous" />
    <link rel="preload" href="<?php echo BASE_URL; ?>assets/fonts/poppins/Poppins-Bold.woff" as="font" type="font/woff" crossorigin="anonymous" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo BASE_URL . $currentLang; ?>/">
    <meta property="og:title" content="<?php echo __('site_title'); ?>">
    <meta property="og:description" content="<?php echo __('site_description'); ?>">
    <meta property="og:image" content="<?php echo BASE_URL; ?>assets/images/hero.avif">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo BASE_URL . $currentLang; ?>/">
    <meta property="twitter:title" content="<?php echo __('site_title'); ?>">
    <meta property="twitter:description" content="<?php echo __('site_description'); ?>">
    <meta property="twitter:image" content="<?php echo BASE_URL; ?>assets/images/hero.avif">

    <meta name="google-site-verification" content="OCAMf2BjT4UPfyDPVsu8cFrKaDQPUgS48Ni5OUZeiOU" />

    <?php if ($currentLang === 'ar'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Zain:wght@200;300;400;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --font-main: 'Zain', sans-serif !important;
        }
        body, input, button, textarea, select {
            font-family: 'Zain', sans-serif !important;
        }
        [dir="rtl"] .font-size-4 { font-size: 3.5rem; }
        [dir="rtl"] .font-size-3 { font-size: 2.2rem; }
    </style>
    <?php endif; ?>
    <style id="critical-css">
        <?php
            $style_path = __DIR__ . '/../assets/css/style.css';
            $grid_path = __DIR__ . '/../assets/css/grid.css';
            if (file_exists($style_path)) {
                $style_css = file_get_contents($style_path);
                echo str_replace('../', BASE_URL . 'assets/', $style_css);
            }
            if (file_exists($grid_path)) {
                echo file_get_contents($grid_path);
            }
        ?>
    </style>

    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>assets/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>assets/images/favicon-16x16.png">
    <link rel="manifest" href="<?php echo BASE_URL; ?>assets/images/site.webmanifest">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      "name": "UAE.GIFT",
      "image": "<?php echo BASE_URL; ?>assets/images/hero.avif",
      "@id": "<?php echo BASE_URL; ?>",
      "url": "<?php echo BASE_URL; ?>",
      "telephone": "+9710506565129",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Flat 1103, 11th Floor, Affini Building, Oud Metha Rd, AI Jaddaf",
        "addressLocality": "Dubai",
        "addressRegion": "Dubai",
        "postalCode": "00000",
        "addressCountry": "AE"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 25.2285,
        "longitude": 55.3214
      },
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday"
        ],
        "opens": "00:00",
        "closes": "23:59"
      },
      "sameAs": [
        "https://t.me/UAE_GIFT_PRICE",
        "https://www.instagram.com/uae.gift"
      ]
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "UAE.GIFT",
      "url": "<?php echo BASE_URL; ?>",
      "logo": "<?php echo BASE_URL; ?>assets/images/logo.svg"
    }
    </script>
    <?php if (!empty($filteredOptions)):
        $firstOpt = reset($filteredOptions);
    ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Product",
      "name": "<?php echo e(__("brand_{$defaultBrand}", $selectedBrandInfo['name'] ?? $defaultBrand)); ?> Gift Card",
      "image": "<?php echo BASE_URL . e($pricingData[$defaultBrand]['logo']); ?>",
      "description": "Buy <?php echo e(__("brand_{$defaultBrand}", $selectedBrandInfo['name'] ?? $defaultBrand)); ?> gift cards in Dubai. Authentic digital cards with instant delivery.",
      "brand": {
        "@type": "Brand",
        "name": "<?php echo e($selectedBrandInfo['name'] ?? $defaultBrand); ?>"
      },
      "offers": {
        "@type": "AggregateOffer",
        "url": "<?php echo BASE_URL . $currentLang; ?>/",
        "priceCurrency": "USD",
        "lowPrice": "<?php echo number_format((float)$firstOpt['price_digital'], 2, '.', ''); ?>",
        "highPrice": "<?php echo number_format((float)end($filteredOptions)['price_digital'], 2, '.', ''); ?>",
        "offerCount": "<?php echo count($filteredOptions); ?>"
      }
    }
    </script>
    <?php endif; ?>

</head>
<body>
    <div class="grid-line-bg" style="max-width: 1200px; top: 40px;"><img src="<?php echo BASE_URL; ?>assets/images/grid-line.svg" alt="UAE Gift Card Background Grid" width="1200" height="681" aria-hidden="true" fetchpriority="high" loading="eager"></div>
    <div class="main relative overhide">

        <header class="top-menu">
            <div class="center d-flex just-between align-center">

                <div class="logo"><img src="<?php echo BASE_URL; ?>assets/images/logo.svg" width="119" height="24" alt="UAE.GIFT Logo" loading="eager"></div>
                <nav class="menu m-hide">
                    <a href="#"><?php echo __('home'); ?></a>
                    <a href="#whyus"><?php echo __('why_us'); ?></a>
                    <a href="#pricing"><?php echo __('pricing'); ?></a>
                    <a href="#contact"><?php echo __('contact'); ?></a>
                </nav>

                <div class="d-flex align-center gap-10">

                    <div class="btn-sm toggle-theme">
                        <span class="icon theme-light-icon icon-moon-stars icon-size-22"></span>
                        <span class="icon theme-dark-icon icon-sun icon-size-22"></span>
                    </div>

                    <div class="drop-down">
                        <div class="drop-down-btn d-flex align-center gap-10 pointer">
                            <div class="drop-down-img">
                                <img class="selected-img" src="<?php echo BASE_URL; ?>assets/images/flag/<?php echo $currentLang === 'ar' ? 'emirates' : 'uk'; ?>.svg" width="28" height="28" alt="<?php echo $currentLang === 'ar' ? 'Arabic' : 'English'; ?> Language" loading="eager">
                            </div>
                            <div class="selected-text m-hide"><?php echo $currentLang === 'ar' ? __('lang_ar') : __('lang_en'); ?></div>
                            <span class="icon icon-arrow-down icon-size-16"></span>
                        </div>

                        <input type="text" class="selected-option" name="lang" value="<?php echo $currentLang; ?>" id="" hidden>

                        <div class="drop-down-list">
                            <div class="drop-option d-flex gap-10 align-center <?php echo $currentLang === 'en' ? 'active' : ''; ?>" data-url="<?php echo BASE_URL; ?>en/">
                                <div class="drop-option-img" data-option="en"><img src="<?php echo BASE_URL; ?>assets/images/flag/uk.svg" width="28" height="28" alt="English Language" loading="lazy"></div>
                                <span><?php echo __('lang_en'); ?></span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center <?php echo $currentLang === 'ar' ? 'active' : ''; ?>" data-url="<?php echo BASE_URL; ?>ar/">
                                <div class="drop-option-img" data-option="ar"><img src="<?php echo BASE_URL; ?>assets/images/flag/emirates.svg" width="28" height="28" alt="Arabic Language" loading="lazy"></div>
                                <span><?php echo __('lang_ar'); ?></span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </header>

        <main>
        <section class="hero-section">
            <div class="center text-center">
                <div class="hero-content">
                    <h1 class="font-size-4 color-title line60 mb-20"><?php echo __('hero_title'); ?></h1>
                    <span class="font-size-1-2"><?php echo __('hero_subtitle'); ?></span>
                </div>

                <div class="hero-img">
                    <div class="img">
                        <picture>
                            <source media="(max-width: 600px)" 
                                srcset="<?php echo BASE_URL; ?>assets/images/hero-mobile.avif 1x, <?php echo BASE_URL; ?>assets/images/hero-mobile-2x.avif 2x"
                                type="image/avif" 
                                width="382" 
                                height="243"> 
                            <img src="<?php echo BASE_URL; ?>assets/images/hero.avif" 
                                width="650" 
                                height="414" 
                                alt="Official Gift Card Distributor Dubai" 
                                style="width: 100%; height: auto;"> 
                        </picture>
                    </div>
                </div>
            </div>
        </section>

        <section id="whyus" class="section">
            <div class="center">
                <div class="d-flex-wrap just-around align-center gap-40">
                    <div class="basis400 grow-1">
                        <h2 class="line60 font-size-3 color-title"><?php echo __('why_trust_title'); ?></h2>
                        <p class="pd-td-30"><?php echo __('why_trust_text'); ?></p>
                        <div class="d-flex align-center gap-40 text-center">
                            <div>
                                <div class="font-size-3 color-primary line60">+8</div>
                                <div class="font-size-1-2"><span><?php echo __('years'); ?></span></div>
                            </div>
                            <div>
                                <div class="font-size-3 color-primary line60">+20</div>
                                <div class="font-size-1-2"><span><?php echo __('brands'); ?></span></div>
                            </div>
                            <div>
                                <div class="font-size-3 color-primary line60">100%</div>
                                <div class="font-size-1-2"><span><?php echo __('satisfaction'); ?></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="basis400 m-hide"><img src="<?php echo BASE_URL; ?>assets/images/why.avif" width="400" height="354" alt="Trustworthy Gift Card Distribution" loading="lazy"></div>
                </div>
            </div>
        </section>

        <!-- product table section -->
        <section id="pricing" class="section">
            <div class="center">

                <div class="text-center mb-20">
                    <h2 class="line60 color-title font-size-3"><?php echo __('pricing_title'); ?></h2>
                    <span><?php echo __('pricing_subtitle'); ?></span>
                </div>

                <div class="fields table-fliters d-flex-wrap align-center mb-20  gap-10">

                    <div class="drop-down grow-1">
                        <div class="drop-down-btn d-flex align-center gap-10 pointer">
                            <div class="drop-down-img">
                                <img class="selected-img" src="<?php echo BASE_URL . e($selectedBrandInfo['logo'] ?? 'assets/images/brand/default.png'); ?>" alt="<?php echo e($selectedBrandInfo['name'] ?? ''); ?> Logo" width="100" height="100" style="width:28px;" loading="lazy">
                            </div>
                            <div class="selected-text"><?php echo e(__("brand_{$defaultBrand}", $selectedBrandInfo['name'] ?? __('select_brand'))); ?></div>
                            <span class="icon icon-arrow-down icon-size-16  lt-auto"></span>
                        </div>

                        <input type="text" class="selected-option" name="brand" value="<?php echo e($defaultBrand); ?>" id="" hidden>

                        <div class="drop-down-list">
                            <?php foreach ($allBrands as $b): ?>
                            <div class="drop-option d-flex gap-10 align-center <?php echo $b['code'] === $defaultBrand ? 'active' : ''; ?>">
                                <div class="drop-option-img" data-option="<?php echo e($b['code']); ?>"><img src="<?php echo BASE_URL . e($b['logo']); ?>" alt="<?php echo e($b['name']); ?> Logo" width="100" height="100" style="width:28px;" loading="lazy"></div>
                                <span><?php echo e(__("brand_{$b['code']}", $b['name'])); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="drop-down grow-1">
                        <div class="drop-down-btn d-flex align-center gap-10 pointer">
                            <div class="drop-down-img">
                                <img class="selected-img" src="<?php echo BASE_URL . e($selectedCountryInfo['flag'] ?? 'assets/images/flag/default.png'); ?>" alt="<?php echo e($selectedCountryInfo['name'] ?? ''); ?> Flag" width="28" height="28" style="width:28px;" loading="lazy">
                            </div>
                            <div class="selected-text"><?php echo e($countryNames[$defaultCountry] ?? __('select_country')); ?></div>
                            <span class="icon icon-arrow-down icon-size-16  lt-auto"></span>
                        </div>

                        <input type="text" class="selected-option" name="country" value="<?php echo e($defaultCountry); ?>" id="" hidden>

                        <div class="drop-down-list">
                            <?php foreach ($allCountries as $c): ?>
                            <div class="drop-option d-flex gap-10 align-center <?php echo $c['code'] === $defaultCountry ? 'active' : ''; ?>">
                                <div class="drop-option-img" data-option="<?php echo e($c['code']); ?>"><img src="<?php echo BASE_URL . e($c['flag']); ?>" alt="<?php echo e($c['name']); ?> Flag" width="28" height="28" style="width:28px;" loading="lazy"></div>
                                <span><?php echo e(__("country_{$c['code']}", $c['name'])); ?> (<?php echo e($c['currency']); ?>)</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="drop-down grow-1">
                        <div class="drop-down-btn d-flex align-center gap-10 pointer">
                            <span class="color-bright"><?php echo __('pack_size'); ?></span>
                            <div class="selected-text"><?php echo __('pack_of'); ?> <?php echo e($defaultPackSize); ?></div>
                            <span class="icon icon-arrow-down icon-size-16 lt-auto"></span>
                        </div>

                        <input type="text" class="selected-option" name="pack_size" value="<?php echo e($defaultPackSize); ?>" id="" hidden>

                        <div class="drop-down-list">
                            <?php
                            $packsToDisplay = !empty($ssrPackSizes) ? $ssrPackSizes : $allPackSizes;
                            foreach ($packsToDisplay as $size):
                            ?>
                            <div class="drop-option d-flex gap-10 align-center <?php echo $size == $defaultPackSize ? 'active' : ''; ?>" data-option="<?php echo e($size); ?>">
                                <span><?php echo __('pack_of'); ?> <?php echo e($size); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mode-toggle m-grow-1">
                        <button class="mode-btn active" id="modeDigitalBtn" type="button"><?php echo __('digital'); ?></button>
                        <button class="mode-btn" id="modePhysicalBtn" type="button"><?php echo __('physical'); ?></button>
                    </div>
                </div>

                <!-- table products -->
                <div class="table-wrap">
                    <table id="productPricingTable">
                        <thead>
                            <tr>
                                <th class="text-center"><?php echo __('brand'); ?></th>
                                <th class="text-left"><?php echo __('denomination'); ?></th>
                                <th class="text-left"><?php echo __('country'); ?></th>
                                <th class="text-left"><?php echo __('qty'); ?></th>
                                <th class="text-left"><?php echo __('price_card'); ?></th>
                                <th class="text-left"><?php echo __('total_price'); ?></th>
                                <th class="text-center"><?php echo __('buy'); ?></th>
                            </tr>
                        </thead>

                        <tbody id="priceTableBody">
                            <?php
                            $options = $pricingData[$defaultBrand]['options'][$defaultCountry] ?? [];
                            $filteredOptions = array_filter($options, function($opt) use ($defaultPackSize) {
                                return $opt['pack_size'] == $defaultPackSize;
                            });

                            if (empty($filteredOptions)):
                            ?>
                            <tr><td colspan="7" class="text-center"><?php echo __('no_products'); ?></td></tr>
                            <?php
                            else:
                            $USD_TO_AED = $exchangeRates['USD'] ?? 3.673;
                            foreach ($filteredOptions as $opt):
                                // Default SSR view is Digital
                                $pricePerCard = (float)$opt['price_digital'];
                                $totalPrice = $pricePerCard * $defaultPackSize;

                                $priceInAED = number_format($pricePerCard * $USD_TO_AED, 2, '.', '');
                                $totalInAED = number_format($totalPrice * $USD_TO_AED, 2, '.', '');

                                $curr = $opt['currency'];
                                $cardSymbol = ($curr === 'USD' ? '$' : ($curr === 'GBP' ? '£' : ($curr === 'TRY' ? 'TL' : ($curr === 'AED' ? 'AED' : ($curr === 'EUR' ? '€' : $curr)))));
                            ?>
                            <tr>
                                <td data-label="<?php echo __('brand'); ?>" class="text-center">
                                    <div class="brand-logo m-auto">
                                        <img src="<?php echo BASE_URL . e($pricingData[$defaultBrand]['logo']); ?>" alt="<?php echo e($pricingData[$defaultBrand]['name']); ?> Logo" width="100" height="100" loading="lazy">
                                    </div>
                                </td>
                                <td data-label="<?php echo __('denomination'); ?>">
                                    <span><?php echo e($opt['denomination']); ?> <?php echo e($cardSymbol); ?></span><br>
                                    <span class="color-bright font-size-0-9"><?php echo __('digital'); ?> · <?php echo e($opt['currency']); ?></span>
                                </td>
                                <td data-label="<?php echo __('country'); ?>"><?php echo e($countryNames[$defaultCountry] ?? $defaultCountry); ?></td>
                                <td data-label="<?php echo __('qty'); ?>"><?php echo e($defaultPackSize); ?></td>
                                <td data-label="<?php echo __('price_card'); ?>">
                                    <span>$<?php echo e(number_format($pricePerCard, 2, '.', '')); ?></span><br>
                                    <span class="color-bright font-size-0-9">~ <?php echo e($priceInAED); ?> AED</span>
                                </td>
                                <td data-label="<?php echo __('total_price'); ?>">
                                    <span>$<?php echo e(number_format($totalPrice, 2, '.', '')); ?></span><br>
                                    <span class="color-bright font-size-0-9">~ <?php echo e($totalInAED); ?> AED</span>
                                </td>
                                <td class="text-center" data-label="<?php echo __('buy'); ?>">
                                    <a href="tel:+9710506565129" class="btn">
                                        <span class="icon icon-calling icon-size-18"></span>
                                        <?php echo __('call_to_order'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>


            </div>
        </section>

        <section class="section">
            <div class="center">
                <div class="text-center mb-20">
                    <h2 class="line60 color-title font-size-3"><?php echo __('advantages_title'); ?></h2>
                    <span><?php echo __('advantages_subtitle'); ?></span>
                </div>

                <div class="d-flex-wrap gap-30">
                    <div class="basis200 bg-gr-light border pd-20 grow-1 radius-20 overhide relative">
                        <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 200px;"><img src="<?php echo BASE_URL; ?>assets/images/grid-line-3.svg" width="195" height="180" alt="Grid Background" aria-hidden="true"></div>
                        <div class="relative">
                            <div class="mb-10"><span class="icon icon-verify icon-size-48 icon--primary "></span></div>
                            <h3 class="mb-5 color-title"><?php echo __('adv1_title'); ?></h3>
                            <p class="line20"><?php echo __('adv1_text'); ?></p>
                        </div>
                    </div>
                    <div class="basis200 bg-gr-light border pd-20 grow-1 radius-20 overhide relative">
                        <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 200px;"><img src="<?php echo BASE_URL; ?>assets/images/grid-line-3.svg" width="195" height="180" alt="Grid Background" aria-hidden="true"></div>
                        <div class="relative">
                            <div class="mb-10"><span class="icon icon-stopwatch icon-size-48 icon--primary "></span></div>
                            <h3 class="mb-5 color-title"><?php echo __('adv2_title'); ?></h3>
                            <p class="line20"><?php echo __('adv2_text'); ?></p>
                        </div>
                    </div>
                    <div class="basis200 bg-gr-light border pd-20 grow-1 radius-20 overhide relative">
                        <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 200px;"><img src="<?php echo BASE_URL; ?>assets/images/grid-line-3.svg" width="195" height="180" alt="Grid Background" aria-hidden="true"></div>
                        <div class="relative">
                            <div class="mb-10"><span class="icon icon-tag-price icon-size-48 icon--primary "></span></div>
                            <h3 class="mb-5 color-title"><?php echo __('adv3_title'); ?></h3>
                            <p class="line20"><?php echo __('adv3_text'); ?></p>
                        </div>
                    </div>
                    <div class="basis200 bg-gr-light border pd-20 grow-1 radius-20 overhide relative">
                        <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 200px;"><img src="<?php echo BASE_URL; ?>assets/images/grid-line-3.svg" width="195" height="180" alt="Grid Background" aria-hidden="true"></div>
                        <div class="relative">
                            <div class="mb-10"><span class="icon icon-headphone icon-size-48 icon--primary "></span></div>
                            <h3 class="mb-5 color-title"><?php echo __('adv4_title'); ?></h3>
                            <p class="line20"><?php echo __('adv4_text'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

         <!-- comments section -->
        <section class="section overhide">
            <div class="center">
                <div class="d-flex-wrap just-around align-center gap-40 overhide">
                    <div class="grow-1 m-hide">
                        <div class="max-w400">
                            <img src="<?php echo BASE_URL; ?>assets/images/contact-us.avif" width="400" height="542" alt="Contact UAE.GIFT Support" loading="lazy">
                        </div>
                    </div>
                    <div class="basis400 grow-8 overhide">
                        <div class="mb-20">
                            <h2 class="line60 font-size-3 color-title"><?php echo __('testimonials_title'); ?></h2>
                            <span><?php echo __('testimonials_subtitle'); ?></span>
                        </div>
                        <div id="comments-slider" class="swiper">
                            <div class="swiper-wrapper mb-20" id="testimonialsContainer">
                        <?php foreach ($testimonials as $t): ?>
                        <div class="swiper-slide">
                            <div class="slide-comment">
                                <div class="d-flex align-center just-between gap-20 mb-10">
                                    <div class="d-flex align-center gap-10">
                                        <div class="user-img"><img src="<?php echo BASE_URL . e($t['image']); ?>" width="100" height="100" alt="Customer <?php echo e($t['name']); ?>" loading="lazy"></div>
                                        <div class="line20">
                                            <div class="color-title font-size-0-9"><?php echo e($t['name']); ?></div>
                                            <div class="color-bright font-size-0-8"><?php echo e($t['date']); ?></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="stars"><img src="<?php echo BASE_URL; ?>assets/images/stars.svg" width="86" height="15" alt="5 Stars Rating" loading="lazy"></div>
                                        <div class="font-size-0-8 color-green"><span class="icon icon-size-16 icon--success"></span> <?php echo __('verified'); ?></div>
                                    </div>
                                </div>
                                <p class="font-size-0-9"><?php echo e($t['text']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-10">
                                <div class="btn-sm com-slide-prev pointer"><span class="icon icon-arrow-left icon-size-18"></span></div>
                                <div class="btn-sm com-slide-next pointer"><span class="icon icon-arrow-right icon-size-18"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <article class="section">
            <div class="center text-content">
                <h2 class="line60 color-title font-size-3"><?php echo __('seo_section_title'); ?></h2>
                <p><?php echo __('seo_section_p1'); ?></p>
                <p><?php echo __('seo_section_p2'); ?></p>
                <p><?php echo __('seo_section_p3'); ?></p>
                <p><?php echo __('seo_section_p4'); ?></p>
                <p><?php echo __('seo_section_p5'); ?></p>
            </div>
        </article>

        <section class="section">
            <div class="center">
                <div class="text-center mb-20">
                    <h2 class="line60 color-title font-size-3"><?php echo __('faqs_title'); ?></h2>
                    <span><?php echo __('faqs_subtitle'); ?></span>
                </div>

                <div class="faq-list border radius-20" id="faqContainer">
                    <?php foreach ($faqs as $index => $faq): ?>
                    <div class="faq-item <?php echo ($index === count($faqs) - 1 ? '' : 'border-b'); ?>">
                        <div class="faq-head d-flex align-center gap-10 pd-20 pointer">
                            <span class="color-primary"><?php echo e($faq['id']); ?></span>
                            <h3 class="color-title"><?php echo e($faq['question']); ?></h3>
                            <span class="icon icon-add icon-size-22 lt-auto"></span>
                        </div>
                        <div class="faq-content border-t pd-20">
                            <p><?php echo e($faq['answer']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="contact" class="section">
            <div class="center">
                <div class="contact-box border bg-gr-light radius-20 overhide relative">
                    <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 300px;"><img src="<?php echo BASE_URL; ?>assets/images/grid-line-3.svg" width="195" height="180" alt="Grid Background" aria-hidden="true"></div>
                    <div class="text-left mb-20 relative">
                        <h2 class="line60 color-primary font-size-3"><?php echo __('get_in_touch'); ?></h2>
                        <span><?php echo __('get_in_touch_subtitle'); ?></span>
                    </div>

                    <div class="d-flex-wrap align-center gap-40 relative">

                        <div class="contact-form basis300 grow-1 border radius-20 pd-20 relative z-1">
                            <form action="#" method="POST" id="contactForm">
                                <div class="d-flex-wrap gap-20">
                                    <div class="input-item basis200 grow-1">
                                        <div class="input-label"><?php echo __('name'); ?></div>
                                        <div class="input">
                                            <input type="text" name="name" placeholder="<?php echo __('your_name'); ?>" required>
                                            <div class="input-icon"><span class="icon icon-size-22"></span></div>
                                        </div>
                                    </div>

                                    <div class="input-item basis200 grow-1">
                                        <div class="input-label"><?php echo __('mobile'); ?></div>
                                        <div class="input">
                                            <input type="tel" name="mobile" placeholder="<?php echo __('your_mobile'); ?>" required>
                                            <div class="input-icon"><span class="icon icon-size-22"></span></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="input-item">
                                    <div class="input-label"><?php echo __('email'); ?></div>
                                    <div class="input">
                                        <input type="email" name="email" placeholder="<?php echo __('your_email'); ?>" required>
                                        <div class="input-icon"><span class="icon icon-size-22"></span></div>
                                    </div>
                                </div>

                                <div class="input-item">
                                    <div class="input-label"><?php echo __('subject'); ?></div>
                                    <div class="input">
                                        <input type="text" name="subject" placeholder="<?php echo __('your_subject'); ?>">
                                        <div class="input-icon"><span class="icon icon-size-22"></span></div>
                                    </div>
                                </div>

                                <div class="input-item">
                                    <div class="input-label"><?php echo __('message_text'); ?></div>
                                    <textarea name="message" id="message" placeholder="<?php echo __('your_message'); ?>" rows="3" required></textarea>
                                </div>
                                <div class="d-flex">
                                    <button type="submit" class="btn-primary radius-100"><?php echo __('send_message'); ?> <span class="icon icon-send icon-size-22 icon--white"></span></button>
                                </div>
                            </form>
                        </div>

                        <div class="contact-info basis300 grow-1 relative">
                            <div class="grid-line-bg" style="top: 50%;transform: translateY(-50%)scale(1.3);"><img src="<?php echo BASE_URL; ?>assets/images/grid-line-2.svg" width="631" height="631" alt="Grid Background" aria-hidden="true"></div>
                            <div class="max-w400 m-auto relative">
                                <div class="mb-20">
                                    <div class="d-flex align-center color-title font-size-1-2"><span class="icon icon-location icon-size-24"></span> <span class="ml-10"><?php echo __('address'); ?></span></div>
                                    <p>Flat 1103, 11th Floor, Affini Building, Oud Metha Rd, AI Jaddaf, Dubai, United Arab Emirates</p>
                                </div>
                                <div class="mb-20">
                                    <div class="d-flex align-center color-title font-size-1-2"><span class="icon icon-mail icon-size-24"></span> <span class="ml-10"><?php echo __('email'); ?></span></div>
                                    <p>info@uea.gift</p>
                                </div>

                                <div class="d-flex-wrap gap-20 mb-20">
                                    <div>
                                        <div class="d-flex align-center color-title font-size-1-2"><span class="icon"></span> <span class="ml-10"><?php echo __('phone'); ?></span></div>
                                        <p>+971 050 656 5129</p>
                                    </div>
                                    <div>
                                        <div class="d-flex align-center color-title font-size-1-2"><span class="icon"></span> <span class="ml-10"><?php echo __('whatsapp'); ?></span></div>
                                        <p>+971 056 380 3107</p>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex align-center color-title font-size-1-2 mb-10"><span class="icon"></span> <span class="ml-10"><?php echo __('follow_us'); ?></span></div>
                                    <div class="d-flex gap-10">
                                        <a href="" class="social-btn"><span class="icon icon-telegram icon-size-22 icon--primary "></span></a>
                                        <a href="" class="social-btn"><span class="icon icon-instagram icon-size-22 icon--primary "></span></a>
                                        <a href="" class="social-btn"><span class="icon icon-youtube icon-size-22 icon--primary "></span></a>
                                        <a href="" class="social-btn"><span class="icon icon-x icon-size-22 icon--primary "></span></a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        </main>

        <footer class="footer">
            <div class="center">
                <div class="d-flex-wrap just-between align-center gap-20 pd-td-30 border-b border-t">
                    <div class="logo"><img src="<?php echo BASE_URL; ?>assets/images/logo.svg" width="119" height="24" alt="UAE.GIFT Logo" loading="lazy"></div>
                    <nav class="menu">
                        <a href="#"><?php echo __('home'); ?></a>
                        <a href="#whyus"><?php echo __('why_us'); ?></a>
                        <a href="#pricing"><?php echo __('pricing'); ?></a>
                        <a href="#contact"><?php echo __('contact'); ?></a>
                    </nav>
                </div>
                <div class="text-center pd-td-20">
                    <?php echo __('all_rights'); ?>
                </div>
            </div>
        </footer>

    </div>

    <script>
        // Use api.php instead of static data.json
        const API_URL = '<?php echo BASE_URL; ?>api.php';
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const SWIPER_JS_URL = '<?php echo BASE_URL; ?>assets/js/swiper-bundle.min.js';
        const SWIPER_CSS_URL = '<?php echo BASE_URL; ?>assets/css/swiper-bundle.min.css';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js" defer></script>

</body>
</html>
