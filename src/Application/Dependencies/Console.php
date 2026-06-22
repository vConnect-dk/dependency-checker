<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Dependencies;

use League\CLImate\CLImate;
use League\CLImate\Exceptions\InvalidArgumentException;
use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;
use Vconnect\IntegrityChecker\Analysis\Data\Dependencies\Result;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;
use Vconnect\IntegrityChecker\Application\ConsoleInterface;
use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;
use Vconnect\IntegrityChecker\Application\Registry\DefectsState;

class Console implements ConsoleInterface
{
    private const ARG_MAGENTO_2_ROOT = 'magento2root';
    private const ARG_FOLDERS = 'folders';
    private const ARG_HELP = 'help';

    public function __construct(
        private readonly DefectsState $defectsState,
        private readonly CLImate      $cli
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
            self::ARG_FOLDERS => [
                'description' => 'List of folders to scan, separated by space.' .
                    ' If not provided, scan will be run for <dim>src</dim> and <dim>app</dim>.',
                'required' => false
            ],
            self::ARG_HELP => [
                'longPrefix' => 'help',
                'description' => 'Prints this help message',
                'noValue' => true,
            ],
        ]);
    }

    /**
     * Print result message for package.
     */
    public function printOutput(DefectiveResultInterface|Result $result): void
    {
        $this->defectsState->registerResult($result);

        if (!$result->hasDefects() && !$result->hasNotices()) {
            return;
        }

        $this->cli->out('------------------------------------------------------------');
        $this->cli->out(
            sprintf(
                'Package %s has %s(s).',
                $result->getPackageName(),
                $result->hasDefects() ? 'defect' : 'notice'
            )
        );

        $defects = $result->getResult();

        if (!empty($defects['composer'][DependencyInterface::TYPE_SOFT]) ||
            !empty($defects['composer'][DependencyInterface::TYPE_HARD])
        ) {
            $this->printComposerMissedDependencies($defects['composer']);
        }

        if (!empty($defects['composer'][DependencyInterface::TYPE_EXCESSIVE])) {
            $this->printExcessiveComposerDependencies($defects['composer'][DependencyInterface::TYPE_EXCESSIVE]);
        }

        if (!(empty($defects['module'][DependencyInterface::TYPE_EXPECTED]))) {
            $this->printModuleXmlMissedDependencies($defects['module'][DependencyInterface::TYPE_EXPECTED]);
        }

        if (!(empty($defects['module'][DependencyInterface::TYPE_EXCESSIVE]))) {
            $this->printExcessiveModuleXmlDependencies($defects['module'][DependencyInterface::TYPE_EXCESSIVE]);
        }
    }

    /**
     * Format and print.
     */
    private function printModuleXmlMissedDependencies(array $missedDependencies): void
    {
        $this->cli->backgroundRed('Missed dependencies in etc/module.xml');

        $this->printModules($missedDependencies);

        $this->cli->br();
    }

    private function printExcessiveModuleXmlDependencies(array $deps): void
    {
        $this->cli->bold()
                  ->yellow('[Notice]')
                  ->yellow('Potentially excessive dependencies in etc/module.xml:');

        $this->printModules($deps);

        $this->cli->br();
    }

    /**
     * Format and print.
     */
    private function printComposerMissedDependencies(array $missedDependencies): void
    {
        $this->cli->backgroundRed('Missed dependencies in composer.json');
        if ($missedDependencies[DependencyInterface::TYPE_SOFT]) {
            $this->cli->bold()->yellow('Suggest:');
            foreach ($missedDependencies[DependencyInterface::TYPE_SOFT] as $suggest) {
                $this->cli->tab()->out(sprintf('"%s": "*",', $suggest));
            }
        }
        if ($missedDependencies[DependencyInterface::TYPE_HARD]) {
            $this->cli->bold()->yellow('Require:');
            foreach ($missedDependencies[DependencyInterface::TYPE_HARD] as $require) {
                $this->cli->tab()->out(sprintf('"%s": "*",', $require));
            }
        }
        $this->cli->br();
    }

    private function printExcessiveComposerDependencies(array $deps): void
    {
        $this->cli->bold()
                  ->yellow('[Notice]')
                  ->yellow('There are potentially excessive Composer dependencies:');

        $this->printComposerPackages($deps);
        $this->cli->br();
    }

    public function getStatusCode(): int
    {
        return $this->defectsState->hasDefects() ? 1 : 0;
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

        $m2root = $this->cli->arguments->get(self::ARG_MAGENTO_2_ROOT);
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

        $folders = explode(' ', $this->cli->arguments->get(self::ARG_FOLDERS));
        foreach ($folders as $folder) {
            if (!is_dir($root . $folder)) {
                $this->cli->yellow(
                    sprintf(
                        'Notice: Can not find directory "%s". Please check your input parameters.',
                        $root . $folder
                    )
                );
                $this->cli->dim(sprintf('Path "%s" should be relative to Magento 2 Directory.', $folder));
            }
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
        $argv = $_SERVER['argv'];
        $argv[2] = implode(' ', array_unique(array_slice($argv, 2)));
        $this->cli->arguments->parse($argv);
    }

    private function printModules(array $deps): void
    {
        foreach ($deps as $moduleName) {
            $this->cli->tab()->out(sprintf('<module name="%s"/>', $moduleName));
        }
    }

    private function printComposerPackages(array $deps): void
    {
        foreach ($deps as $dependency) {
            $this->cli->tab()->out(sprintf('"%s": "*",', $dependency));
        }
    }
}
