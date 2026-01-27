<?php
require_once '../system/includes/functions.php';
session_destroy();
redirect('login.php');
