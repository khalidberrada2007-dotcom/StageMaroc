<?php
/**
 * PHPMailer autoloader for StageMaroc
 * Simple PSR-4-like autoloader for PHPMailer classes.
 */
spl_autoload_register(function ($class) {
    // Only handle PHPMailer classes
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Not a PHPMailer class, skip
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

