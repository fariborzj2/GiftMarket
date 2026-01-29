<?php
/**
 * Standalone cron script for refreshing exchange rates.
 * Usage: php cron.php
 */

require_once __DIR__ . '/system/includes/functions.php';
require_once __DIR__ . '/system/plugins/telegram-bot/TelegramBot.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting cron tasks...\n";

echo "1. Refreshing exchange rate...\n";
$newRate = refreshExchangeRate();

if ($newRate !== false) {
    echo "   - Success! New USD to AED rate: " . $newRate . "\n";
} else {
    echo "   - Failed to refresh exchange rate.\n";
}

echo "2. Checking Telegram Bot schedule...\n";
try {
    $bot = new TelegramBot();
    $bot->checkScheduleAndPublish();
    echo "   - Done.\n";
} catch (Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] All tasks completed.\n";
