<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;

use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class RegExpFileAnalysis
{
    private ?string $regExp = null;

    private PackagesRegistry $packagesRegistry;

    public function __construct()
    {
        $this->packagesRegistry = PackagesRegistry::getInstance();
    }

    /**
     * Get list of required packages dependencies from php file.
     *
     * @param \SplFileInfo $file
     * @param string[] $currentModuleNamespaces
     *
     * @return string[] - list of packages mentioned inside the file.
     */
    public function analyzeFile(\SplFileInfo $file, array $currentModuleNamespaces): array
    {
        $contents = \php_strip_whitespace($file->getPathname());

        if ($file->getExtension() === 'phtml') {
            $contents = $this->stripeHtml($contents);
        }

        if (!preg_match_all($this->getRegExp(), $contents, $matches)) {
            return [];
        }

        $candidates = array_unique($matches['class']);
        $dependenciesInfo = [];

        foreach ($candidates as $referenceModule) {
            $referenceModule = str_replace('_', '\\', $referenceModule);

            if (array_reduce(
                $currentModuleNamespaces,
                fn($carry, $namespace) => $carry || str_starts_with($referenceModule, $namespace)
            )) {
                continue;
            }

            $dependenciesInfo[] = $this->packagesRegistry->getRealPackageNamespace($referenceModule) ??
                $this->getMagentoNamespace($referenceModule);
        }

        return $dependenciesInfo;
    }

    private function getMagentoNamespace(string $referenceModule): string
    {
        $pieces = explode('\\', $referenceModule);

        return $pieces[0] . '\\' . $pieces[1];
    }

    /**
     * Collects php content inside of template file and return it as result.
     *
     * @param string $contents
     *
     * @return string
     */
    private function stripeHtml(string $contents): string
    {
        return (string)preg_replace_callback(
            '~(<\?(php|=)\s+.*\?>)~sU',
            function (array $matches) use ($contents, &$contentsWithoutHtml)
            {
                $contentsWithoutHtml .= $matches[1];

                return $contents;
            },
            $contents
        );
    }

    /**
     * @return string
     */
    private function getRegExp():string
    {
        if ($this->regExp) {
            return $this->regExp;
        }

        $namespaces = $this->packagesRegistry->getAllProjectNamespaces();
        $availableVendors = [];

        foreach ($namespaces as $namespace) {
            $availableVendors[] = explode('\\', $namespace)[0];
        }

        /**
         * Regular expression to lookup classes and namespaces inside of module php/phtml files.
         * Vendor names are taken from PackagesProvider Registry to limit number of outputs variations which could be returned.
         * Reg.exp. for project with only Magneto namespace: '~(\B[\\\\]|[^\\\\]\b)((Magento([_\\]))[a-zA-Z0-9]{2,})~';
         * Expected to match in next strings:
         * Magento\Zzz in cases:
         * use \Magento\Zzz\Module\Some\Class;
         * use Magento\Zzz\Module\Some\Class;
         * $a = \Magento\Zzz\Module\Some\Class::class;
         * use \Magento\Zzz\Rewrite\Magento\Catalog\Something;
         * $b = Magento\Zzz\Rewrite\Magento\Catalog\Something::class; (in case if file does not have namespace);
         */
        $this->regExp = '~(\B[\\\\]|[^\\\\]\b)(?<class>(?<module>(' .
            implode('[_\\\\]|', array_unique($availableVendors)) .
            '[_\\\\])[a-zA-Z0-9]{2,})' .
            '([a-zA-Z0-9_\\\\]{2,})?)\b~';

        return $this->regExp;
    }
}
