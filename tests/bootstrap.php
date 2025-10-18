<?php
require __DIR__ . '/../app/config/config.php';

// Basic autoloader for app classes (same logic used by test scripts)
spl_autoload_register(function ($className) {
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];

    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
