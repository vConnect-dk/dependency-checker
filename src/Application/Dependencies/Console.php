<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Dependencies;

use League\CLImate\CLImate;
use League\CLImate\Exceptions\InvalidArgumentException;
use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;
use Vconnect\IntegrityChecker\Analysis\Data\ResultInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;
use Vconnect\IntegrityChecker\Application\ConsoleInterface;
use Vconnect\IntegrityChecker\Application\Registry\DefectsState;

class Console implements ConsoleInterface
{
    private const ARG_MAGENTO_2_ROOT = 'magento2root';
    private const ARG_FOLDERS = 'folders';
    private const ARG_HELP = 'help';
    private DefectsState $defectsState;
    private CLImate $cli;

    public function __construct()
    {
        $this->defectsState = new DefectsState();
        $this->cli = new CLImate();
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
     *
     * @param DefectiveResultInterface $result
     */
    public function printOutput(ResultInterface $result): void
    {
        $this->defectsState->registerResult($result);

        if (!$result->hasDefects()) {
            return;
        }

        $this->cli->out('------------------------------------------------------------');
        $this->cli->out(sprintf('Package %s has defects(s).', $result->getPackageName()));

        $defects = $result->getDefects();

        if (!empty($defects['composer'][DependencyInterface::TYPE_SOFT]) ||
            !empty($defects['composer'][DependencyInterface::TYPE_HARD])
        ) {
            $this->printComposerMissedDependencies($defects['composer']);
        }

        if (!(empty($defects['module']))) {
            $this->printModuleXmlMissedDependencies($defects['module']);
        }
    }

    /**
     * Format and print.
     *
     * @param array $missedDependencies
     */
    private function printModuleXmlMissedDependencies(array $missedDependencies): void
    {
        $this->cli->backgroundRed('Missed dependencies in etc/module.xml');

        foreach ($missedDependencies as $moduleName) {
            $this->cli->tab()->out(sprintf('<module name="%s"/>', $moduleName));
        }

        $this->cli->br();
    }

    /**
     * Format and print.
     *
     * @param array $missedDependencies
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

        if (!is_file($m2root . DIRECTORY_SEPARATOR . 'composer.lock')) {
            $this->cli->error('"composer.lock" file was not found in Magento 2 Directory.');

            return false;
        }

        $folders = explode(' ', $this->cli->arguments->get(self::ARG_FOLDERS));
        foreach ($folders as $folder) {
            if (!is_dir(ROOT_DIR . $folder)) {
                $this->cli->yellow(
                    sprintf(
                        'Notice: Can not find directory "%s". Please check your input parameters.',
                        ROOT_DIR . $folder
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
}
