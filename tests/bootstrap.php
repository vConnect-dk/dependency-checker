<?php

require_once __DIR__ . '/../vendor/autoload.php';

$sandboxPath = __DIR__ . '/sandbox';

if (file_exists($sandboxPath . '/composer.json') & !file_exists($sandboxPath . '/composer.lock')) {
    echo "Running composer install in sandbox..." . PHP_EOL;
    $originalDir = getcwd();
    chdir($sandboxPath);
    exec('composer install --no-interaction --no-progress', $output, $returnVar);
    chdir($originalDir);

    if ($returnVar !== 0) {
        echo "Composer install failed in sandbox!" . PHP_EOL;
        echo implode(PHP_EOL, $output) . PHP_EOL;
        exit(1);
    }
    echo "Composer install completed successfully in sandbox." . PHP_EOL;
}
