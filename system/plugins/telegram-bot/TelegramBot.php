<?php
require_once __DIR__ . '/TelegramAPI.php';

class TelegramBot {
    private $db;
    private $api;

    public function __construct() {
        $this->db = db();
        $token = getSetting('telegram_bot_token', '');
        $this->api = new TelegramAPI($token);
    }

    /**
     * Main function to publish prices to all configured channels
     */
    public function publishPrices($manual = false) {
        // Check if bot is enabled
        if (!$manual && getSetting('telegram_bot_enabled', '0') !== '1') {
            return false;
        }

        $channels = $this->getChannels();
        if (empty($channels)) {
            $this->log('error', 'No channels configured for publishing.', '');
            return false;
        }

        $products = $this->getProductsToPublish();
        if (empty($products)) {
            $this->log('warning', 'No products found matching the Telegram config.', '');
            return false;
        }

        $template = getSetting('telegram_message_template', "*{brand} Gift Card* {country}\n\n{type}: {price} {currency}\n\n_Last update: {last_update}_");
        $useEmojis = getSetting('telegram_use_emojis', '1') === '1';
        $priceType = getSetting('telegram_price_type', 'both');
        $usdToAed = getSetting('usd_to_aed', '3.673');

        $messages = $this->formatMessages($products, $template, $useEmojis, $priceType, $usdToAed);

        if (empty($messages)) {
            $this->log('warning', 'No messages formatted. Check template and price type settings.', '');
            return false;
        }

        $totalSent = 0;
        foreach ($channels as $channel) {
            foreach ($messages as $msg) {
                $result = $this->api->sendMessage($channel['channel_id'], $msg);
                if ($result && isset($result['ok']) && $result['ok']) {
                    $totalSent++;
                } else {
                    $this->log('error', "Failed to send to {$channel['channel_id']}: " . ($result['description'] ?? 'Unknown error'), json_encode($result));
                }
            }
        }

        if ($totalSent > 0) {
            $this->log('success', "Successfully sent $totalSent messages to Telegram channels.", '');
            updateSetting('telegram_last_publish_date', date('Y-m-d'));
            return true;
        }

        return false;
    }

    /**
     * Fetch configured channels
     */
    private function getChannels() {
        try {
            return $this->db->query("SELECT * FROM telegram_channels")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Fetch products that match the Telegram config (enabled brands/countries)
     */
    private function getProductsToPublish() {
        try {
            $query = "SELECT p.*, pk.pack_size, pk.price_digital, pk.price_physical,
                             b.name as brand_name, c.name as country_name, c.code as country_code
                      FROM products p
                      JOIN telegram_config tc ON p.brand = tc.brand_code AND p.country = tc.country_code
                      JOIN brands b ON p.brand = b.code
                      JOIN countries c ON p.country = c.code
                      JOIN product_packs pk ON p.id = pk.product_id
                      WHERE tc.enabled = 1
                      ORDER BY b.sort_order ASC, c.sort_order ASC, p.denomination ASC, pk.pack_size ASC";
            return $this->db->query($query)->fetchAll();
        } catch (Exception $e) {
            $this->log('error', 'Database error fetching products: ' . $e->getMessage(), '');
            return [];
        }
    }

    /**
     * Format product data into messages grouped by Brand and Country
     */
    private function formatMessages($products, $template, $useEmojis, $priceType, $usdToAed) {
        $messages = [];
        $lastUpdate = date('H:i');

        // Step 1: Group by Brand and Country
        $grouped = [];
        foreach ($products as $p) {
            $key = $p['brand'] . '_' . $p['country'];
            $grouped[$key][] = $p;
        }

        foreach ($grouped as $key => $rows) {
            $brandName = $rows[0]['brand_name'];
            $countryName = $rows[0]['country_name'];
            $countryCode = $rows[0]['country_code'];

            if ($useEmojis) {
                $countryName = $this->getCountryEmoji($countryCode) . ' ' . $countryName;
            }

            // Step 2: Group by Product (Denomination) within the brand/country group
            $productGroups = [];
            foreach ($rows as $r) {
                $productGroups[$r['id']][] = $r;
            }

            $currentMessage = "";
            foreach ($productGroups as $pid => $packs) {
                $firstPack = $packs[0];
                // Block Header: $100 USA Apple iTunes Gift Card
                $itemBlock = "{$firstPack['denomination']} {$countryName} {$brandName} Gift Card\n";

                foreach ($packs as $pk) {
                    if (($priceType === 'digital' || $priceType === 'both') && $pk['price_digital'] > 0) {
                        $typeStr = "Digital" . ($pk['pack_size'] > 1 ? " (Pack of {$pk['pack_size']})" : "");
                        $itemBlock .= "{$typeStr}:  {$pk['currency']}" . number_format($pk['price_digital'], 2) . "\n";
                    }
                    if (($priceType === 'physical' || $priceType === 'both') && $pk['price_physical'] > 0) {
                        $typeStr = "Physical" . ($pk['pack_size'] > 1 ? " (Pack of {$pk['pack_size']})" : "");
                        $itemBlock .= "{$typeStr}: {$pk['currency']}" . number_format($pk['price_physical'], 2) . "\n";
                    }
                }

                // Avoid Telegram 4096 character limit
                if (strlen($currentMessage . $itemBlock) > 3800) {
                    $messages[] = trim($currentMessage) . "\n\n_Last update: {$lastUpdate}_";
                    $currentMessage = $itemBlock . "\n";
                } else {
                    $currentMessage .= $itemBlock . "\n";
                }
            }

            if (!empty(trim($currentMessage))) {
                $messages[] = trim($currentMessage) . "\n\n_Last update: {$lastUpdate}_";
            }
        }

        return $messages;
    }

    /**
     * Convert 2-letter country code to Emoji
     */
    private function getCountryEmoji($code) {
        $code = strtoupper($code);
        if (strlen($code) !== 2) return '🌍';

        $first = ord($code[0]) + 127397;
        $second = ord($code[1]) + 127397;

        return mb_chr($first, 'UTF-8') . mb_chr($second, 'UTF-8');
    }

    /**
     * Log an event to the database
     */
    public function log($status, $message, $response = '') {
        try {
            $stmt = $this->db->prepare("INSERT INTO telegram_logs (status, message, response) VALUES (?, ?, ?)");
            $stmt->execute([$status, $message, $response]);
        } catch (Exception $e) {
            // Fallback to error_log if DB logging fails
            error_log("TelegramBot Log Error: " . $e->getMessage());
        }
    }

    /**
     * Check if it's time to publish based on schedule
     */
    public function checkScheduleAndPublish() {
        if (getSetting('telegram_bot_enabled', '0') !== '1') return;

        $publishTime = getSetting('telegram_publish_time', '09:00');
        $lastDate = getSetting('telegram_last_publish_date', '');
        $today = date('Y-m-d');
        $now = date('H:i');

        if ($lastDate !== $today && $now >= $publishTime) {
            $this->publishPrices();
        }
    }
}
