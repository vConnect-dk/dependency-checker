<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Domain\Package;

class XmlConfigFiles implements DependenciesScannerInterface
{
    private const FILE_MASKS = ['di.xml', 'system.xml', 'extension_attributes.xml'];
    private RegExpFileAnalysis $regExpFileAnalysis;

    /**
     * @param RegExpFileAnalysis $regExpFileAnalysis
     */
    public function __construct(RegExpFileAnalysis $regExpFileAnalysis)
    {
        $this->regExpFileAnalysis = $regExpFileAnalysis;
    }

    /**
     * Search for dependencies inside the module directory.
     * Scan di.xml', 'system.xml', 'extension_attributes.xml' files for PHP classes with regexp
     * and collect corresponding modules which are required by the package to work properly.
     *
     * @param Package $package
     *
     * @return string[] - list of packages founded as dependencies inside package's files.
     */
    public function lookupDependencies(Package $package): array
    {
        $collectedDependencies = [];

        foreach ($package->getPackageFiles() as $file) {
            if (in_array($file->getFilename(), self::FILE_MASKS)) {
                $collectedDependencies[] = $this->regExpFileAnalysis->analyzeFile($file, $package->getPackageNamespaces());
            }
        }

        return array_unique(array_merge([], ...$collectedDependencies));
    }
}
