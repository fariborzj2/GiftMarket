<?php
require_once __DIR__ . '/bootstrap.php';
// Only clear the customer session, leaving any admin session intact.
unset($_SESSION['customer_id']);
redirect('login.php');
