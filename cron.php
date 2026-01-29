<?php
/**
 * Standalone cron script for refreshing exchange rates.
 * Usage: php cron.php
 */

require_once __DIR__ . '/system/includes/functions.php';

echo "Starting exchange rate refresh...\n";

$newRate = refreshExchangeRate();

if ($newRate !== false) {
    echo "Success! New USD to AED rate: " . $newRate . "\n";
} else {
    echo "Failed to refresh exchange rate. Check your API connectivity.\n";
}
