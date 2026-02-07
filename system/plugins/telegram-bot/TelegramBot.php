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
                      WHERE tc.enabled = 1 AND p.status = 1
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

        // Fetch Settings/Defaults
        $currencySymbolsStr = getSetting('telegram_currency_symbols', '$, USD, AED, EUR, GBP, TL');
        $currencySymbols = array_map('trim', explode(',', $currencySymbolsStr));
        $exchangeRate = (float)getSetting('exchange_rate', 1.0);
        $targetCurrency = $this->escapeMarkdown(getSetting('target_currency', 'AED'));

        // Extract Block Templates
        $digitalRowTpl = "• {size} -> {price} {currency}";
        $physicalRowTpl = "• {size} -> {price} {currency}";

        $hasDigitalBlock = preg_match('/\[DIGITAL_PACKS\](.*?)\[\/DIGITAL_PACKS\]/s', $template, $m1);
        if ($hasDigitalBlock) $digitalRowTpl = trim($m1[1]);

        $hasPhysicalBlock = preg_match('/\[PHYSICAL_PACKS\](.*?)\[\/PHYSICAL_PACKS\]/s', $template, $m2);
        if ($hasPhysicalBlock) $physicalRowTpl = trim($m2[1]);

        // Group by Brand and Country
        $grouped = [];
        foreach ($products as $p) {
            $key = $p['brand'] . '_' . $p['country'];
            $grouped[$key][] = $p;
        }

        foreach ($grouped as $key => $rows) {
            $brandName = $this->escapeMarkdown($rows[0]['brand_name']);
            $countryName = $this->escapeMarkdown($rows[0]['country_name']);
            $countryCode = $this->escapeMarkdown($rows[0]['country_code']);
            $customEmoji = $rows[0]['telegram_emoji'];

            $emoji = "";
            if ($useEmojis) {
                $emoji = !empty($customEmoji) ? $customEmoji : $this->getCountryEmoji($countryCode);
            }

            $productGroups = [];
            foreach ($rows as $r) {
                $productGroups[$r['id']][] = $r;
            }

            foreach ($productGroups as $pid => $packs) {
                $firstPack = $packs[0];
                $symbol = $firstPack['display_symbol'] ?: getCurrencySymbol($firstPack['currency']);
                $currency = $this->escapeMarkdown($symbol);
                $denomValue = trim(str_replace($currencySymbols, '', $firstPack['denomination']));

                // Format Digital Packs
                $digitalPacksStr = "";
                if ($priceType === 'digital' || $priceType === 'both') {
                    foreach ($packs as $pk) {
                        if ($pk['price_digital'] > 0) {
                            $totalPrice = (float)$pk['price_digital'] * (int)$pk['pack_size'];
                            $priceVal = (float)round($totalPrice, 2);
                            $convPrice = (float)round($totalPrice * $exchangeRate, 2);
                            $digitalPacksStr .= str_ireplace(
                                ['{size}', '{currency}', '{price}', '{converted_price}', '{target_currency}'],
                                [$pk['pack_size'], $currency, $priceVal, $convPrice, $targetCurrency],
                                $digitalRowTpl
                            ) . "\n";
                        }
                    }
                }

                // Format Physical Packs
                $physicalPacksStr = "";
                if ($priceType === 'physical' || $priceType === 'both') {
                    foreach ($packs as $pk) {
                        if ($pk['price_physical'] > 0) {
                            $totalPrice = (float)$pk['price_physical'] * (int)$pk['pack_size'];
                            $priceVal = (float)round($totalPrice, 2);
                            $convPrice = (float)round($totalPrice * $exchangeRate, 2);
                            $physicalPacksStr .= str_ireplace(
                                ['{size}', '{currency}', '{price}', '{converted_price}', '{target_currency}'],
                                [$pk['pack_size'], $currency, $priceVal, $convPrice, $targetCurrency],
                                $physicalRowTpl
                            ) . "\n";
                        }
                    }
                }

                // Assembly
                $currentMsg = $template;

                if ($hasDigitalBlock) {
                    $currentMsg = str_replace($m1[0], trim($digitalPacksStr), $currentMsg);
                }
                if ($hasPhysicalBlock) {
                    $currentMsg = str_replace($m2[0], trim($physicalPacksStr), $currentMsg);
                }

                $replacements = [
                    '{emoji}' => $emoji,
                    '{brand}' => $brandName,
                    '{country}' => $countryCode,
                    '{country_name}' => $countryName,
                    '{currency}' => $currency,
                    '{denomination}' => $this->escapeMarkdown($denomValue),
                    '{last_update_time}' => $lastUpdate,
                    '{last_update}' => $lastUpdate,
                    '{lastupdate}' => $lastUpdate
                ];
                $currentMsg = str_ireplace(array_keys($replacements), array_values($replacements), $currentMsg);

                // Auto-escape underscores in Telegram handles (e.g., @UAE_GIFT_PRICE)
                $currentMsg = preg_replace_callback('/(@[a-zA-Z0-9_]+)/', function($m) {
                    return str_replace('_', '\\_', $m[0]);
                }, $currentMsg);

                // Fallbacks
                if (!$hasDigitalBlock && !empty(trim($digitalPacksStr)) && stripos($template, '{digital_packs}') === false) {
                    $currentMsg .= "\n\n" . trim($digitalPacksStr);
                }
                if (!$hasPhysicalBlock && !empty(trim($physicalPacksStr)) && stripos($template, '{physical_packs}') === false) {
                    $currentMsg .= "\n\n" . trim($physicalPacksStr);
                }
                if (stripos($currentMsg, $lastUpdate) === false) {
                    $currentMsg .= "\n\n🕒 Last update: " . $lastUpdate;
                }

                $currentMsg = preg_replace('/\{[a-zA-Z0-9_-]+\}/i', '', $currentMsg);
                $messages[] = trim($currentMsg);
            }
        }

        return $messages;
    }

    /**
     * Escape special characters for Telegram Markdown
     */
    private function escapeMarkdown($text) {
        if (empty($text)) return '';
        // Note: We escape underscores, asterisks, backticks and opening brackets
        // which are the main formatting characters in Telegram's basic Markdown.
        return str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $text);
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
