<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Domain\FileClassScanner;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\Package\DiXml\DiXml;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\PhpFilesScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Model\DependencyInterface;

class PhpFiles implements DependenciesScannerInterface
{
    private const FILE_MASKS = ['php', 'phtml'];

    private RegExpFileAnalysis $regExpFileAnalysis;
    private ?array $pluginMap = null;

    /**
     * @param RegExpFileAnalysis $regExpFileAnalysis
     */
    public function __construct(RegExpFileAnalysis $regExpFileAnalysis)
    {
        $this->regExpFileAnalysis = $regExpFileAnalysis;
    }

    /**
     * Search for dependencies inside the module directory.
     * Scan *.php and *.phtml files for PHP classes with regexp and collect corresponding modules which are required
     * by the package to work properly.
     *
     * @param Package $package
     *
     * @return PhpFilesScannerResult - interface of packages founded as dependencies inside package's files.
     */
    public function lookupDependencies(Package $package): PhpFilesScannerResult
    {
        $collectedDependencies = [
            DependencyInterface::TYPE_SOFT => [],
            DependencyInterface::TYPE_HARD=>[]
        ];
        /** @var DiXml $diXml */
        $diXml = $package->getDiXml();
        if ($diXml) {
            $this->pluginMap = $diXml->getPluginMap();
        }
        foreach ($package->getPackageFiles() as $file) {
            if (\in_array($file->getFileInfo()->getExtension(), self::FILE_MASKS)) {
                $result = $this->regExpFileAnalysis->analyzeFile($file, $package->getPackageNamespaces());
                if ($this->pluginMap && $this->isFilePlugin($file->getFilename(), $file->getPathname())) {
                    $collectedDependencies[DependencyInterface::TYPE_SOFT][] = $result;
                } else {
                    $collectedDependencies[DependencyInterface::TYPE_HARD][] = $result;
                }
//                $collectedDependencies[] = $this->regExpFileAnalysis->analyzeFile($file, $package->getPackageNamespaces());
            }
        }

        return $this->setUpScannerResult($collectedDependencies);
    }

    /**
     * @param string $fileName
     * @param $path
     * @return bool
     * @throws \Exception
     */
    private function isFilePlugin(string $fileName, $path): bool
    {
        $result = false;
        foreach ($this->pluginMap as $pluginFile => $type) {
            $split = explode('\\', $pluginFile);
            $pluginFileName = array_pop($split) . '.php';

            if ($pluginFileName === $fileName) {
                $className = FileClassScanner::getInstance()->getClassName($path);
                if ($pluginFile === $className) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $collectedDependencies
     * @return PhpFilesScannerResult
     */
    private function setUpScannerResult(array $collectedDependencies): PhpFilesScannerResult
    {
        $scannerResult = new PhpFilesScannerResult();

        $scannerResult->setSoftDependencies(array_unique(
            array_merge([],
            ...$collectedDependencies[DependencyInterface::TYPE_SOFT]
            )));
        $scannerResult->setHardDependencies(array_unique(
            array_merge([],
            ...$collectedDependencies[DependencyInterface::TYPE_HARD]
            )));

        return $scannerResult;
    }
}
