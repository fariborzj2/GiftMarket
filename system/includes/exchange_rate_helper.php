<?php
/**
 * Refreshes the USD to AED exchange rate from a public API.
 * @return float|bool The new rate on success, false on failure.
 */
function refreshExchangeRate() {
    $apiUrl = 'https://open.er-api.com/v6/latest/USD';

    // Use cURL or file_get_contents
    $ctx = stream_context_create([
        'http' => ['timeout' => 5]
    ]);

    $response = @file_get_contents($apiUrl, false, $ctx);

    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['rates']['AED'])) {
            $newRate = (float)$data['rates']['AED'];

            // Update the setting in database
            updateSetting('usd_to_aed', (string)$newRate);
            updateSetting('last_rate_update', (string)time());

            return $newRate;
        }
    }

    return false;
}

/**
 * Checks if it's time to update the exchange rate and performs the update if needed.
 */
function checkAndAutoUpdateRate() {
    $autoUpdate = (int)getSetting('auto_update_rate', 0);
    if (!$autoUpdate) return;

    $intervalHours = (int)getSetting('update_interval_hours', 12);
    $lastUpdate = (int)getSetting('last_rate_update', 0);

    $currentTime = time();
    $secondsPassed = $currentTime - $lastUpdate;

    if ($secondsPassed >= ($intervalHours * 3600)) {
        refreshExchangeRate();
    }
}
