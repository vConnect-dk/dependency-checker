<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Structure;

use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;
use Vconnect\IntegrityChecker\Application\ConsoleInterface;
use Vconnect\IntegrityChecker\Application\Registry\DefectsState;

class Console implements ConsoleInterface
{
    public function __construct(
        private readonly DefectsState $defectsState
    ) {
    }

    public function printOutput(DefectiveResultInterface $result): void
    {
        $this->defectsState->registerResult($result);

        if (!$result->hasDefects()) {
            return;
        }

        echo PHP_EOL;
        echo sprintf("Package \"%s\" has incorrect structure.\nMissed folders/files:", $result->getPackageName());

        $this->printTree($result->getResult());
        echo PHP_EOL;
    }

    /**
     * Recursively print the tree.
     *
     * @param array $tree
     * @param int $tabs
     */
    private function printTree(array $tree, int $tabs = 1): void
    {
        foreach ($tree as $name => $stem) {
            echo PHP_EOL;
            echo str_repeat("\t", $tabs);

            if (is_array($stem)) {
                echo "- $name";
                $this->printTree($stem, $tabs + 1);
            } else {
                echo "- $stem";
            }
        }
    }

    public function getStatusCode(): int
    {
        return $this->defectsState->hasDefects() ? 1 : 0;
    }

    public function validateParameters(): bool
    {
        $argc = $_SERVER['argc'];
        $argv = array_unique($_SERVER['argv']);

        if ($argc < 2) {
            echo "\e[31mExpected first parameter as Magento 2 Root Directory.\e[39m" . PHP_EOL;
            return false;
        }

        if (!is_file($argv[1] . DIRECTORY_SEPARATOR . 'composer.lock')) {
            echo "\e[31m\"composer.lock\" file was not found in Magento 2 Directory.\e[39m" . PHP_EOL;
            return false;
        }

        for ($i = 2; $i < $argc; $i++) {
            if (!is_dir(ROOT_DIR . $argv[$i])) {
                echo sprintf(
                        "Notice: Can not find directory \"%s\". Please check your input parameters.",
                        ROOT_DIR . $argv[$i]
                    ) . PHP_EOL
                    . sprintf("Path \"%s\" should be relative to Magento 2 Directory.", $argv[$i])
                    . PHP_EOL;
            }
        }

        return true;
    }

    public function printHelp(): void
    {
        echo "\e[32mHelp\e[39m" . PHP_EOL;
        echo 'Tool to check if modules are follow to standard module structure. Usage:' . PHP_EOL;
        echo 'php bin/structure [Magento2 root] {folder1} {folder2}' . PHP_EOL;
        echo '[Magento2 root] - path to Magento 2 project root directory.' . PHP_EOL;
        echo '{folder1} {folder2} - list of relative folders to scan, separated by space. ';
        echo 'If not provided, scan will be run for "src" and "app".' . PHP_EOL;
    }
}
