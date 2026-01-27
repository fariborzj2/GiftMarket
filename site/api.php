<?php
require_once __DIR__ . '/../system/includes/functions.php';

$appData = json_decode(file_get_contents(__DIR__ . '/../assets/js/data.json'), true);

// Override pricing data with database content
$products = db()->query("SELECT * FROM products WHERE status = 1")->fetchAll();

// Group products by brand and country to match the JSON structure
$pricingData = [];
foreach ($products as $p) {
    $brand = $p['brand'];
    $country = $p['country'];

    if (!isset($pricingData[$brand])) {
        // Fallback logo and name from JSON if available
        $pricingData[$brand] = [
            'name' => $appData['pricingData'][$brand]['name'] ?? ucfirst($brand),
            'logo' => $appData['pricingData'][$brand]['logo'] ?? 'assets/images/brand/default.png',
            'options' => []
        ];
    }

    if (!isset($pricingData[$brand]['options'][$country])) {
        $pricingData[$brand]['options'][$country] = [];
    }

    $pricingData[$brand]['options'][$country][] = [
        'denomination' => $p['denomination'],
        'price' => $p['price'],
        'currency' => $p['currency']
    ];
}

$appData['pricingData'] = $pricingData;

header('Content-Type: application/json');
echo json_encode($appData);
