<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain;

use FilesystemIterator;
use Vconnect\IntegrityChecker\Domain\Package\Composer\Json;
use Vconnect\IntegrityChecker\Domain\Package\Config;
use Vconnect\IntegrityChecker\Exception\FileNotFoundException;
use Vconnect\IntegrityChecker\Domain\Scanner\FileClassScanner;

class Package
{
    public const MAGENTO_PACKAGE_TYPE = 'magento2-module';
    public const MAGENTO_LIBRARY_TYPE = 'magento2-library';
    public const MAGENTO_COMPONENT_TYPE = 'magento2-component';
    public const UNKNOWN_PACKAGE_TYPE = 'unknown';

    private string $path;
    private ?array $packageFiles = null;
    private array $loadedFileClasses = [];
    private ?Json $composerJson = null;
    private Config $config;
    private FileClassScanner $fileClassScanner;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->fileClassScanner = new FileClassScanner();
        $this->path = $path;
        $this->config = new Config($this);
    }

    /**
     * Return Package Path.
     *
     * @return string
     */
    public function getPackagePath(): string
    {
        return $this->path;
    }

    public function getPackageType(): string
    {
        $type = null;
        try {
            $type = $this->getComposerJson()->getPackageType();
        } catch (FileNotFoundException) {}

        if ($type !== null) {
            return $type;
        }

        try {
            $this->getConfig()->getModuleXml();
            return self::MAGENTO_PACKAGE_TYPE;
        } catch (FileNotFoundException) {}

        return self::UNKNOWN_PACKAGE_TYPE;
    }

    /**
     * Get dependencies from 'require' section
     *
     * @return array
     * @throws FileNotFoundException
     */
    public function getComposerRequirePackages(bool $withDev = true): array
    {
        $require = $this->getComposerJson()->getRequire();

        if ($withDev) {
            $require = array_merge($require, $this->getComposerJson()->getRequireDev());
        }

        return $require;
    }

    /**
     * Get dependencies from 'suggest' section
     *
     * @return array
     * @throws FileNotFoundException
     */
    public function getComposerSuggestPackages(): array
    {
        return $this->getComposerJson()->getSuggest();
    }

    /**
     * Get declared dependencies in module.xml file.
     *
     * @return array
     * @throws FileNotFoundException
     */
    public function getModuleXmlDependencies(): array
    {
        return $this->getConfig()->getModuleXml()->getDependencies();
    }

    /**
     * Load all files in the package.
     *
     * @return \SplFileInfo[]
     */
    public function getPackageFiles(): array
    {
        return $this->getPackageFilesList();
    }

    /**
     * Resolve package namespaces either from composer.json or etc/module.xml.
     * Namespaces are being returned without trailing slashes.
     *
     * @return array|string[]
     */
    public function getPackageNamespaces(): array
    {
        $namespaces = $this->resolveNamespacesFromComposerJson();

        if (empty($namespaces)) {
            $namespaces = $this->resolveNamespaceFromModuleXml() ? [$this->resolveNamespaceFromModuleXml()] : [];
        }

        return $namespaces;
    }

    private function resolveNamespacesFromComposerJson(): array
    {
        try {
            $namespaces = $this->getComposerJson()->getNamespaces();
            $namespaces = array_map(fn ($namespace) => trim($namespace, '\\'), $namespaces);
        } catch (FileNotFoundException) {
            $namespaces = [];
        }

        return $namespaces;
    }

    private function resolveNamespaceFromModuleXml(): ?string
    {
        try {
            $namespace = $this->getConfig()->getModuleXml()->getModuleName();
            $namespace = is_string($namespace) ? str_replace('_', '\\', $namespace) : null;
        } catch (FileNotFoundException) {
            $namespace = null;
        }

        return $namespace;
    }

    /**
     * Get package name from composer.json file.
     *
     * @return string
     */
    public function getPackageName(): string
    {
        try {
            $packageName = $this->getComposerJson()->getPackageName();
        } catch (FileNotFoundException) {
            $packageName = null;
        }

        return $packageName ?? $this->getPackagePath();
    }

    /**
     * Get Package File Info.
     *
     * @return \SplFileInfo[]
     */
    private function getPackageFilesList(): array
    {
        if (!$this->packageFiles) {
            $this->packageFiles = iterator_to_array(
                new \CallbackFilterIterator(
                    new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::SELF_FIRST
                    ),
                    function (\SplFileInfo $fileInfo) {
                        return $fileInfo->isFile() &&
                            !preg_match('/(\/Test\/|\/tests\/|\/Tests\/|\/Test.php)/i', $fileInfo->getPathname());
                    }
                )
            );
        }

        return $this->packageFiles;
    }

    /**
     * Load composer.json file to provide dependencies.
     *
     * @return Json
     * @throws FileNotFoundException
     */
    private function getComposerJson(): Json
    {
        if ($this->composerJson) {
            return $this->composerJson;
        }

        foreach ($this->getPackageFilesList() as $file) {
            if ($file->getFilename() === 'composer.json') {
                $this->composerJson = new Json($file->getPathname());

                return $this->composerJson;
            }
        }
        throw new FileNotFoundException('composer.json', $this->path);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Resolve class reference by path
     *
     * @param string $filePath
     * @return string
     */
    public function getClassReferenceByPath(string $filePath): string
    {
        if (!isset($this->loadedFileClasses[$filePath])) {
            $this->loadedFileClasses[$filePath] = $this->fileClassScanner->getClassName($filePath);
        }
        return $this->loadedFileClasses[$filePath];
    }
}
