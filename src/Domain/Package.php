<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain;

use Vconnect\IntegrityChecker\Domain\Package\Composer\Json;
use Vconnect\IntegrityChecker\Domain\Package\Config\ModuleXml;
use Vconnect\IntegrityChecker\Exception\FileNotFoundException;
use Vconnect\IntegrityChecker\Domain\Package\Config\XmlDomDocuments;
use Vconnect\IntegrityChecker\Domain\Scanner\FileClassScanner;

class Package
{
    private string $path;
    private ?array $packageFiles = null;
    private ?Json $composerJson = null;
    private ?ModuleXml $moduleXml = null;
    private ?XmlDomDocuments $xmlDomDocuments = null;
    private FileClassScanner $fileClassScanner;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->fileClassScanner = new FileClassScanner();
        $this->path = $path;
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
        try {
            $resolvedType = $this->getComposerJson()->getPackageType();
        } catch (FileNotFoundException $exception) {
            $resolvedType = null;
        }

        return $resolvedType ?? 'unknown';
    }

    /**
     * Get dependencies from 'require' section
     *
     * @return array
     * @throws FileNotFoundException
     */
    public function getComposerRequirePackages(): array
    {
        return $this->getComposerJson()->getRequire();
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
        return $this->getModuleXml()->getDependencies();
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
            $namespaces = $this->getComposerJson()->getNamespace();
            $namespaces = array_map(fn ($namespace) => trim($namespace, '\\'), $namespaces);
        } catch (FileNotFoundException $exception) {
            $namespaces = [];
        }

        return $namespaces;
    }

    private function resolveNamespaceFromModuleXml(): ?string
    {
        try {
            $namespace = $this->getModuleXml()->getModuleName();
            $namespace = is_string($namespace) ? str_replace('_', '\\', $namespace) : null;
        } catch (FileNotFoundException $exception) {
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
        } catch (FileNotFoundException $exception) {
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
                new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path))
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

    /**
     * Load Module Xml File.
     *
     * @return ModuleXml
     * @throws FileNotFoundException
     */
    private function getModuleXml(): ModuleXml
    {
        if ($this->moduleXml) {
            return $this->moduleXml;
        }

        foreach ($this->getPackageFilesList() as $file) {
            if ($file->getFilename() === 'module.xml') {
                $this->moduleXml = new ModuleXml($file->getPathname());

                return $this->moduleXml;
            }
        }
        throw new FileNotFoundException('module.xml', $this->path);
    }

    /**
     * Returns DOM documents of .xml config files.
     *
     * @return DOMDocument[]
     */
    public function getXmlFilesDomDocuments(): array
    {
        if (!isset($this->xmlDomDocuments)) {
            $this->xmlDomDocuments = new XmlDomDocuments($this->getPackageFiles());
        }

        return $this->xmlDomDocuments->getXmlFilesDomDocuments();
    }

    /**
     * @return array
     */
    public function getPluginMap(): array
    {
        if (!isset($this->xmlDomDocuments)) {
            $this->xmlDomDocuments = new XmlDomDocuments($this->getPackageFiles());
        }

        return $this->xmlDomDocuments->getPluginMap();
    }

    /**
     * @param string $path
     *
     * @return \SplFileInfo
     */
    public function getFile(string $path): \SplFileInfo
    {
        $filePath = $this->path . DIRECTORY_SEPARATOR . $path;
        return new \SplFileInfo($filePath);
    }

    /**
     * Resolve class reference by path
     *
     * @param string $filePath
     * @return string
     */
    public function getClassReferenceByPath(string $filePath): string
    {
        return $this->fileClassScanner->getClassName($filePath);
    }
}
