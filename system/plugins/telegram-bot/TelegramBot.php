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

        $template = getSetting('telegram_message_template', "{emoji} {brand} {gift_card} – {currency} {denomination}");
        $useEmojis = getSetting('telegram_use_emojis', '1') === '1';
        $priceType = getSetting('telegram_price_type', 'both');

        $messages = $this->formatMessages($products, $template, $useEmojis, $priceType);

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
                             b.name as brand_name, c.name as country_name, c.code as country_code,
                             c.telegram_emoji
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
    private function formatMessages($products, $template, $useEmojis, $priceType) {
        $messages = [];
        $lastUpdate = date('H:i');

        // Fetch Settings/Labels
        $labelGiftCard = getSetting('telegram_label_gift_card', 'Gift Card');
        $labelDigital = getSetting('telegram_label_digital', 'Digital');
        $labelPhysical = getSetting('telegram_label_physical', 'Physical');
        $labelPack = getSetting('telegram_label_pack', 'Pack');
        $labelLastUpdate = getSetting('telegram_label_last_update', '🕒 Last update');
        $currencySymbolsStr = getSetting('telegram_currency_symbols', '$, USD, AED, EUR, GBP, TL');
        $currencySymbols = array_map('trim', explode(',', $currencySymbolsStr));
        $packRowTemplate = getSetting('telegram_pack_row_template', "• {pack} {size} → {currency} {price}");
        $exchangeRate = (float)getSetting('exchange_rate', 1.0);
        $targetCurrency = getSetting('target_currency', 'AED');

        // Step 1: Group by Brand and Country
        $grouped = [];
        foreach ($products as $p) {
            $key = $p['brand'] . '_' . $p['country'];
            $grouped[$key][] = $p;
        }

        foreach ($grouped as $key => $rows) {
            // Escape for Markdown
            $brandName = str_replace(['_', '*', '`', '['], '', $rows[0]['brand_name']);
            $countryName = $rows[0]['country_name'];
            $countryCode = $rows[0]['country_code'];
            $customEmoji = $rows[0]['telegram_emoji'];

            $emoji = "";
            if ($useEmojis) {
                $emoji = !empty($customEmoji) ? $customEmoji : $this->getCountryEmoji($countryCode);
            }

            // Step 2: Group by Product (Denomination)
            $productGroups = [];
            foreach ($rows as $r) {
                $productGroups[$r['id']][] = $r;
            }

            foreach ($productGroups as $pid => $packs) {
                $firstPack = $packs[0];
                $currency = $firstPack['currency'];

                // Clean denomination value for the header
                $denomValue = trim(str_replace($currencySymbols, '', $firstPack['denomination']));

                // Item Header
                $replacements = [
                    '{emoji}' => $emoji,
                    '{brand}' => $brandName,
                    '{country}' => $countryCode,
                    '{country_name}' => $countryName,
                    '{gift_card}' => $labelGiftCard,
                    '{currency}' => $currency,
                    '{denomination}' => $denomValue,
                    '{last_update}' => $lastUpdate,
                    '{lastupdate}' => $lastUpdate
                ];
                $itemHeader = str_ireplace(array_keys($replacements), array_values($replacements), $template);

                // Regex cleanup for any remaining {tags} to prevent raw output
                $itemHeader = preg_replace('/\{[a-zA-Z0-9_-]+\}/i', '', $itemHeader);

                // Digital Section
                $digitalPacksStr = "";
                if ($priceType === 'digital' || $priceType === 'both') {
                    $digitalPacks = [];
                    foreach ($packs as $pk) {
                        if ($pk['price_digital'] > 0) {
                            $totalPrice = (float)$pk['price_digital'] * (int)$pk['pack_size'];
                            $priceVal = (float)round($totalPrice, 2);
                            $convPrice = (float)round($totalPrice * $exchangeRate, 2);
                            $packLine = str_ireplace(
                                ['{pack}', '{size}', '{currency}', '{price}', '{converted_price}', '{target_currency}'],
                                [$labelPack, $pk['pack_size'], $currency, $priceVal, $convPrice, $targetCurrency],
                                $packRowTemplate
                            );
                            $digitalPacks[] = preg_replace('/\{[a-zA-Z0-9_-]+\}/i', '', $packLine);
                        }
                    }
                    if (!empty($digitalPacks)) {
                        $digitalPacksStr = $labelDigital . "\n\n" . implode("\n\n", $digitalPacks) . "\n\n";
                    }
                }

                // Physical Section
                $physicalPacksStr = "";
                if ($priceType === 'physical' || $priceType === 'both') {
                    $physicalPacks = [];
                    foreach ($packs as $pk) {
                        if ($pk['price_physical'] > 0) {
                            $totalPrice = (float)$pk['price_physical'] * (int)$pk['pack_size'];
                            $priceVal = (float)round($totalPrice, 2);
                            $convPrice = (float)round($totalPrice * $exchangeRate, 2);
                            $packLine = str_ireplace(
                                ['{pack}', '{size}', '{currency}', '{price}', '{converted_price}', '{target_currency}'],
                                [$labelPack, $pk['pack_size'], $currency, $priceVal, $convPrice, $targetCurrency],
                                $packRowTemplate
                            );
                            $physicalPacks[] = preg_replace('/\{[a-zA-Z0-9_-]+\}/i', '', $packLine);
                        }
                    }
                    if (!empty($physicalPacks)) {
                        $physicalPacksStr = $labelPhysical . "\n\n" . implode("\n\n", $physicalPacks) . "\n\n";
                    }
                }

                // Final Message Assembly
                $replacements = [
                    '{emoji}' => $emoji,
                    '{brand}' => $brandName,
                    '{country}' => $countryCode,
                    '{country_name}' => $countryName,
                    '{gift_card}' => $labelGiftCard,
                    '{currency}' => $currency,
                    '{denomination}' => $denomValue,
                    '{digital_packs}' => $digitalPacksStr,
                    '{physical_packs}' => $physicalPacksStr,
                    '{last_update_label}' => $labelLastUpdate,
                    '{last_update_time}' => $lastUpdate,
                    '{last_update}' => $lastUpdate,
                    '{lastupdate}' => $lastUpdate
                ];

                $message = str_ireplace(array_keys($replacements), array_values($replacements), $template);

                // Compatibility: If packs tags are missing, append them at the end
                if (stripos($template, '{digital_packs}') === false && !empty($digitalPacksStr)) {
                    $message .= "\n\n" . $digitalPacksStr;
                }
                if (stripos($template, '{physical_packs}') === false && !empty($physicalPacksStr)) {
                    $message .= "\n\n" . $physicalPacksStr;
                }
                if (stripos($template, '{last_update_time}') === false && stripos($template, '{last_update}') === false && stripos($template, '{lastupdate}') === false) {
                    $message .= "\n\n" . $labelLastUpdate . ": " . $lastUpdate;
                }

                // Cleanup and add to messages
                $message = preg_replace('/\{[a-zA-Z0-9_-]+\}/i', '', $message);
                $messages[] = trim($message);
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
