<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Disassembling;

use League\CLImate\CLImate;
use League\CLImate\Exceptions\InvalidArgumentException;
use Vconnect\IntegrityChecker\Analysis\Data\ResultInterface;
use Vconnect\IntegrityChecker\Application\ConsoleInterface;
use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;

class Console implements ConsoleInterface
{
    private const ARG_MAGENTO_2_ROOT = 'magento2root';
    private const ARG_WHITELIST = 'whitelist';
    private const ARG_PACKAGE_EXPLAIN = 'explain';
    private const ARG_NO_CACHE = 'no-cache';
    private const ARG_HELP = 'help';

    public function __construct(
        private readonly CLImate $cli
    ) {
        $this->configureCommand();
    }

    private function configureCommand(): void
    {
        $this->cli->description(
            '<bold>Tool to check integrity of declared dependencies in composer.json and etc/module.xml.</bold>'
        );
        $this->cli->arguments->add([
            self::ARG_MAGENTO_2_ROOT => [
                'description' => 'Path to Magento 2 project root directory',
                'required' => true,
                'castTo' => 'string'
            ],
            self::ARG_WHITELIST => [
                'prefix' => 'w',
                'longPrefix' => self::ARG_WHITELIST,
                'description' => 'Whitelist file or list of modules that should not be removed.' .
                    ' Please specify either path to the file or comma separated list of modules.',
                'required' => false
            ],
            self::ARG_PACKAGE_EXPLAIN => [
                'prefix' => 'e',
                'longPrefix' => self::ARG_PACKAGE_EXPLAIN,
                'description' => 'Package name to explain if it can be removed or not.',
                'required' => false
            ],
            self::ARG_NO_CACHE => [
                'prefix' => 'nc',
                'longPrefix' => self::ARG_NO_CACHE,
                'description' => 'Generate new dependency graph instead of using cache.',
                'noValue' => true,
                'required' => false
            ],
            self::ARG_HELP => [
                'longPrefix' => 'help',
                'description' => 'Prints this help message',
                'noValue' => true,
            ],
        ]);
    }

    public function getNoCacheArgument(): bool
    {
        if ($this->cli->arguments->defined(self::ARG_NO_CACHE)) {
            return $this->cli->arguments->get(self::ARG_NO_CACHE);
        }
        return false;
    }

    public function getMagentoRoot(): string
    {
        return $this->cli->arguments->get(self::ARG_MAGENTO_2_ROOT);
    }

    public function getWhitelist(): string
    {
        return $this->cli->arguments->get(self::ARG_WHITELIST);
    }

    public function getExplainArgument(): ?string
    {
        return $this->cli->arguments->get(self::ARG_PACKAGE_EXPLAIN);
    }

    /**
     * Print result message for package.
     */
    public function printOutput(ResultInterface $result): void
    {
        if ($this->getExplainArgument()) {
            $this->printExplainOutput($result);
        } else {
            $this->printReplaceOutput($result);
        }
    }

    private function printExplainOutput(ResultInterface $result): void
    {
        foreach ($result->getResult() as $message) {
            if ($message['problem']) {
                $this->cli->red()->bold($message['message']);
            } else {
                $this->cli->bold($message['message']);
            }
        }
    }

    private function printReplaceOutput(ResultInterface $result): void
    {
        foreach ($result->getResult() as $generation => $modules) {
            $this->cli->out(sprintf('Layer %s ', $generation));
            foreach ($modules as $result) {
                $this->cli->tab()->out(sprintf('"%s": "*",', $result));
            }
        }
    }

    public function getStatusCode(): int
    {
        return 0;
    }

    public function validateParameters(): bool
    {
        if ($this->cli->arguments->defined(self::ARG_HELP)) {
            /* Would just print help message */
            return false;
        }

        try {
            $this->parseArguments();
        } catch (InvalidArgumentException $e) {
            $this->cli->backgroundLightRed($e->getMessage());

            return false;
        }

        $m2root = $this->getMagentoRoot();
        if (!$m2root) {
            $this->cli->error('Expected first parameter as Magento 2 Root Directory.');

            return false;
        }

        $root = realpath($m2root) . '/';
        DirectoryRegistry::setRoot($root);

        if (!is_file($m2root . DIRECTORY_SEPARATOR . 'composer.lock')) {
            $this->cli->error('"composer.lock" file was not found in Magento 2 Directory.');

            return false;
        }

        return true;
    }

    public function printHelp(): void
    {
        $this->cli->yellow()->bold('Help')->br();
        $this->cli->usage();
    }

    private function parseArguments(): void
    {
        $this->cli->arguments->parse($_SERVER['argv']);
    }
}
