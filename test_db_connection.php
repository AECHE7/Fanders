<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

try {
    $db = Database::getInstance();
    echo 'Database connection successful!';
} catch (Exception $e) {
    echo 'Database connection failed: ' . $e->getMessage();
}
