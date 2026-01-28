<?php
require_once __DIR__ . '/../system/includes/functions.php';

$appData = json_decode(file_get_contents(__DIR__ . '/../assets/js/data.json'), true);

// Override pricing data with database content
$groupedProducts = getGroupedProducts();

// Transform to match the UI JSON structure
$pricingData = [];
foreach ($groupedProducts as $brand => $countries) {
    $pricingData[$brand] = [
        'name' => $appData['pricingData'][$brand]['name'] ?? ucfirst($brand),
        'logo' => $appData['pricingData'][$brand]['logo'] ?? 'assets/images/brand/default.png',
        'options' => []
    ];
    foreach ($countries as $country => $items) {
        foreach ($items as $p) {
            $pricingData[$brand]['options'][$country][] = [
                'denomination' => $p['denomination'],
                'price' => $p['price'],
                'price_digital' => $p['price_digital'],
                'price_physical' => $p['price_physical'],
                'currency' => $p['currency']
            ];
        }
    }
}

$appData['pricingData'] = $pricingData;

header('Content-Type: application/json');
echo json_encode($appData);
