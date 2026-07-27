<?php

declare(strict_types=1);
use Vconnect\IntegrityChecker\Tests\Support\TestApplication;

/**
 * PHPUnit bootstrap (unit + integration).
 *
 * Does not load project bootstrap.php / define App() — production CLI entry points do that.
 * Integration tests go through {@see TestApplication}
 * for container / sandbox isolation (no production Application::reset()).
 *
 * System tests exec bin/* and therefore use the real bootstrap + App() path.
 */

$sandboxPath = __DIR__ . '/sandbox';

if (file_exists($sandboxPath . '/composer.json') && !is_dir($sandboxPath . '/vendor')) {
    echo "Running composer install in sandbox..." . PHP_EOL;
    $originalDir = getcwd();
    chdir($sandboxPath);
    exec('composer install --no-interaction --no-progress --no-dev', $output, $returnVar);
    chdir($originalDir);

    if ($returnVar !== 0) {
        echo "Composer install failed in sandbox!" . PHP_EOL;
        echo implode(PHP_EOL, $output) . PHP_EOL;
        exit(1);
    }

    echo "Composer install completed successfully in sandbox." . PHP_EOL;
}

require_once __DIR__ . '/../vendor/autoload.php';
