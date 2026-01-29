<?php
require_once __DIR__ . '/../system/includes/functions.php';

$appData = json_decode(file_get_contents(__DIR__ . '/../assets/js/data.json'), true);

// Fetch all brands and countries from database for correct naming/logos
$allBrands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
$brandMap = [];
foreach ($allBrands as $b) $brandMap[$b['code']] = $b;

$allCountries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
$countryNames = [];
foreach ($allCountries as $c) {
    $countryNames[$c['code']] = $c['name'] . ' (' . $c['currency'] . ')';
}

// Override pricing data with database content
$groupedProducts = getGroupedProducts();

// Transform to match the UI JSON structure
$pricingData = [];
foreach ($groupedProducts as $brandCode => $countries) {
    $brandInfo = $brandMap[$brandCode] ?? null;
    $pricingData[$brandCode] = [
        'name' => $brandInfo['name'] ?? ($appData['pricingData'][$brandCode]['name'] ?? ucfirst($brandCode)),
        'logo' => $brandInfo['logo'] ?? ($appData['pricingData'][$brandCode]['logo'] ?? 'assets/images/brand/default.png'),
        'options' => []
    ];
    foreach ($countries as $countryCode => $products) {
        foreach ($products as $product) {
            foreach ($product['packs'] as $pack) {
                $pricingData[$brandCode]['options'][$countryCode][] = [
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

$appData['pricingData'] = $pricingData;
$appData['countryNames'] = $countryNames;
$appData['exchangeRates']['USD'] = (float)getSetting('usd_to_aed', $appData['exchangeRates']['USD'] ?? 3.673);

header('Content-Type: application/json');
echo json_encode($appData);
