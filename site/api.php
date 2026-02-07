<?php
require_once __DIR__ . '/../system/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'contact') {
        $name = clean($_POST['name'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $mobile = clean($_POST['mobile'] ?? '');
        $subject = clean($_POST['subject'] ?? '');
        $message = clean($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($mobile) || empty($message)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => __('fill_all_fields', 'Please fill all required fields.')]);
            exit;
        }

        try {
            $stmt = db()->prepare("INSERT INTO contact_messages (name, email, mobile, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $mobile, $subject, $message]);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => __('message_sent_success', 'Your message has been sent successfully!')]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => __('message_sent_error', 'An error occurred while sending your message.')]);
            exit;
        }
    }
}

$lang = getLanguage();

// Load base data
$baseData = json_decode(file_get_contents(__DIR__ . '/../assets/js/data.json'), true);

// Load language-specific data
$langDataFile = __DIR__ . "/../assets/js/data_{$lang}.json";
$langData = [];
if (file_exists($langDataFile)) {
    $langData = json_decode(file_get_contents($langDataFile), true);
}

// Merge language data into base data
$appData = array_merge($baseData, $langData);

// Fetch all brands and countries from database for correct naming/logos
$allBrands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
$brandMap = [];
foreach ($allBrands as $b) $brandMap[$b['code']] = $b;

$allCountries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
$countryNames = [];
foreach ($allCountries as $c) {
    $countryNames[$c['code']] = __("country_{$c['code']}", $c['name']) . ' (' . $c['currency'] . ')';
}

// Override pricing data with database content
$groupedProducts = getGroupedProducts();

// Transform to match the UI JSON structure
$pricingData = [];
foreach ($groupedProducts as $brandCode => $countries) {
    $brandInfo = $brandMap[$brandCode] ?? null;
    $pricingData[$brandCode] = [
        'name' => __("brand_{$brandCode}", $brandInfo['name'] ?? ucfirst($brandCode)),
        'logo' => $brandInfo['logo'] ?? 'assets/images/brand/default.png',
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
                    'currency' => $product['currency'],
                    'display_symbol' => $product['display_symbol']
                ];
            }
        }
    }
}

$appData['pricingData'] = $pricingData;
$appData['countryNames'] = $countryNames;
$appData['exchangeRates']['USD'] = (float)getSetting('usd_to_aed', $appData['exchangeRates']['USD'] ?? 3.673);
$appData['translations'] = [
    'pack_of' => __('pack_of'),
    'digital' => __('digital'),
    'physical' => __('physical'),
    'call_to_order' => __('call_to_order'),
    'no_packs' => __('no_products'),
    'brand' => __('brand'),
    'denomination' => __('denomination'),
    'country' => __('country'),
    'qty' => __('qty'),
    'price_card' => __('price_card'),
    'total_price' => __('total_price'),
    'buy' => __('buy'),
];

header('Content-Type: application/json');
echo json_encode($appData);
