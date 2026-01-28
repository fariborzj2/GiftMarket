<?php
require_once __DIR__ . '/../system/includes/functions.php';

$appData = json_decode(file_get_contents(__DIR__ . '/../assets/js/data.json'), true);

// Fetch all brands and countries from database for correct naming/logos
$allBrands = db()->query("SELECT * FROM brands")->fetchAll();
$brandMap = [];
foreach ($allBrands as $b) $brandMap[$b['code']] = $b;

$allCountries = db()->query("SELECT * FROM countries")->fetchAll();
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
    foreach ($countries as $countryCode => $items) {
        foreach ($items as $p) {
            $pricingData[$brandCode]['options'][$countryCode][] = [
                'denomination' => $p['denomination'],
                'pack_size' => $p['pack_size'],
                'price' => $p['price'],
                'price_digital' => $p['price_digital'],
                'price_physical' => $p['price_physical'],
                'currency' => $p['currency']
            ];
        }
    }
}

$appData['pricingData'] = $pricingData;
$appData['countryNames'] = $countryNames;

header('Content-Type: application/json');
echo json_encode($appData);
