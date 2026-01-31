<?php
require_once 'system/includes/functions.php';
echo "BASE_URL: " . BASE_URL . "\n";
echo "APP_LANG: " . (defined('APP_LANG') ? APP_LANG : 'not defined') . "\n";
echo "URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
