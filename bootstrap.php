<?php declare(strict_types=1);

use Vconnect\IntegrityChecker\Application;

/**
 * @throws ErrorException
 */
function exception_error_handler($severity, $message, $file, $line): void
{
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}

set_error_handler('exception_error_handler');
ini_set('memory_limit', '-1');


if (isset($argv[1])) {
    define('ROOT_DIR', realpath($argv[1]) . '/');
}
if (!empty($GLOBALS['_composer_autoload_path'])) {
    require_once $GLOBALS['_composer_autoload_path'];
} elseif (is_file(__DIR__ . '/../autoload.php')) {
    //Installed as package.
    include_once __DIR__ . '/../autoload.php';
} elseif (is_file(__DIR__ . '/../../../vendor/autoload.php')) {
    //Installed as symlink.
    include_once __DIR__ . '/../../../vendor/autoload.php';
} elseif (is_file(__DIR__ . '/vendor/autoload.php')) {
    //Installed as project.
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    echo 'Can not find vendor autoload.php file.' . PHP_EOL;
    echo 'Please run \'composer install\' and check that' .
        ' Integrity Checker tool is installed as composer package to your project.';
    exit(1);
}

define('PACKAGE_DIR', realpath(__DIR__));

function App()
{
    return Application::get()->getContainer();
}