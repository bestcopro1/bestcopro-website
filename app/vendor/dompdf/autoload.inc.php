<?php

// Shared-hosting deployments can retain the historical PhenX dependency
// folders while Composer's current class map points to the new Dompdf paths.
// Register both layouts before Composer so PDF generation remains atomic
// during a vendor update.
$bestcoproDompdfPrefixes = [
    "FontLib\\" => [
        __DIR__ . "/vendor/dompdf/php-font-lib/src/FontLib",
        __DIR__ . "/vendor/phenx/php-font-lib/src/FontLib",
    ],
    "Svg\\" => [
        __DIR__ . "/vendor/dompdf/php-svg-lib/src/Svg",
        __DIR__ . "/vendor/phenx/php-svg-lib/src/Svg",
    ],
];

spl_autoload_register(function ($class) use ($bestcoproDompdfPrefixes) {
    foreach ($bestcoproDompdfPrefixes as $prefix => $directories) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }
        $relative = substr($class, strlen($prefix));
        $relative = str_replace("\\", DIRECTORY_SEPARATOR, $relative) . ".php";
        foreach ($directories as $directory) {
            $file = $directory . DIRECTORY_SEPARATOR . $relative;
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
}, true, true);

require __DIR__ . "/vendor/autoload.php";
