<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain;

use Adbar\Dot;
use FilesystemIterator;
use DI\FactoryInterface;
use Vconnect\IntegrityChecker\Application\Filesystem\Data\FileInfo;
use Vconnect\IntegrityChecker\Domain\Package\Composer\Json;
use Vconnect\IntegrityChecker\Domain\Package\Config;
use Vconnect\IntegrityChecker\Domain\Scanner\FileClassScanner;
use Vconnect\IntegrityChecker\Exception\FileNotFoundException;
use Vconnect\IntegrityChecker\Utils\RecursiveArrayLeavesIterator;

class Package
{
    public const MAGENTO_PACKAGE_TYPE = 'magento2-module';
    public const MAGENTO_LIBRARY_TYPE = 'magento2-library';
    public const MAGENTO_COMPONENT_TYPE = 'magento2-component';
    public const UNKNOWN_PACKAGE_TYPE = 'unknown';

    private ?Dot $filesTree = null;
    private array $loadedFileClasses = [];
    private ?Json $composerJson = null;

    /**
     * @var string|null SubFolder of Magento 2 module.
     */
    private ?string $subFolder = null;
    private readonly Config $config;

    public function __construct(
        private readonly string           $path,
        private readonly FileClassScanner $fileClassScanner,
        private readonly FactoryInterface $factory
    ) {
        $this->config = $this->factory->make(Config::class, ['package' => $this]);
    }

    /**
     * Return Package Path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getPackageType(): string
    {
        $type = null;
        try {
            $type = $this->getComposerJson()->getPackageType();
        } catch (FileNotFoundException) {
        }

        if ($type !== null) {
            return $type;
        }

        try {
            $this->getConfig()->getModuleXml();
            return self::MAGENTO_PACKAGE_TYPE;
        } catch (FileNotFoundException) {
        }

        return self::UNKNOWN_PACKAGE_TYPE;
    }

    /**
     * Get dependencies from the 'require' section
     */
    public function getComposerRequirePackages(bool $withDev = true): array
    {
        try {
            $require = $this->getComposerJson()->getRequire();

            if ($withDev) {
                return array_merge($require, $this->getComposerJson()->getRequireDev());
            }

            return $require;
        } catch (FileNotFoundException) {
            return [];
        }
    }

    /**
     * Get dependencies from 'suggest' section
     */
    public function getComposerSuggestPackages(): array
    {
        try {
            return $this->getComposerJson()->getSuggest();
        } catch (FileNotFoundException) {
            return [];
        }
    }

    /**
     * Get extension replace section
     */
    public function getComposerReplacePackages(): array
    {
        try {
            return $this->getComposerJson()->getReplace();
        } catch (FileNotFoundException) {
            return [];
        }
    }

    /**
     * Get declared dependencies in module.xml file.
     */
    public function getModuleXmlDependencies(): array
    {
        try {
            return $this->getConfig()->getModuleXml()->getDependencies();
        } catch (FileNotFoundException) {
            return [];
        }
    }

    public function getFile(string $filePath): ?FileInfo
    {
        $files = $this->getFilesTree();
        if ($files->has($filePath)) {
            return $files->get($filePath);
        }

        return $this->subFolder ? $files->get($this->subFolder . DIRECTORY_SEPARATOR . $filePath) : null;
    }

    /**
     * Load all files in the package.
     *
     * @return FileInfo[]
     */
    public function getFiles(?string $directory = null): iterable
    {
        return $directory === null
            ? new RecursiveArrayLeavesIterator($this->getFilesTree()->all())
            : $this->getFilesByDirectory($directory);
    }

    /**
     * Fetch files from requested directory.
     */
    private function getFilesByDirectory(string $directory): iterable
    {
        $files = $this->getFilesTree();

        if ($files->has($directory)) {
            return new RecursiveArrayLeavesIterator($files->get($directory));
        }

        if ($this->subFolder) {
            $directory = $this->subFolder . DIRECTORY_SEPARATOR . $directory;
        }

        return new RecursiveArrayLeavesIterator($files->get($directory) ?? []);
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

        if ($namespaces === []) {
            return $this->resolveNamespaceFromModuleXml() ? [$this->resolveNamespaceFromModuleXml()] : [];
        }

        return $namespaces;
    }

    private function resolveNamespacesFromComposerJson(): array
    {
        try {
            $namespaces = $this->getComposerJson()->getNamespaces();
            $namespaces = array_map(fn ($namespace): string => trim((string) $namespace, '\\'), $namespaces);
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
     */
    public function getName(): string
    {
        try {
            $packageName = $this->getComposerJson()->getPackageName();
        } catch (FileNotFoundException) {
            $packageName = null;
        }

        return $packageName ?? $this->getPath();
    }

    /**
     * Get Package File Info.
     *
     * @return Dot<FileInfo>|FileInfo[]|FileInfo[][]
     */
    public function getFilesTree(): Dot|array
    {
        if ($this->filesTree === null) {
            $packageFiles = [];
            $iterator = new \CallbackFilterIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                ),
                function (\SplFileInfo $fileInfo): bool {
                    $relativeRoot = str_replace($this->path, '', $fileInfo->getPathname());
                    return $fileInfo->isFile() &&
                        !preg_match('/^(\/Test\/|\/tests\/|\/Tests\/|\/Test.php)/i', $relativeRoot);
                }
            );
            foreach ($iterator as $path => $fileInfo) {
                $key = str_replace($this->path . DIRECTORY_SEPARATOR, '', $path);
                $packageFiles[$key] = FileInfo::fromSplFileInfo($fileInfo);
            }

            $this->filesTree = new Dot($packageFiles, parse: true, delimiter: '/');
        }

        return $this->filesTree;
    }

    /**
     * Load composer.json file to provide dependencies.
     *
     * @throws FileNotFoundException
     */
    private function getComposerJson(): Json
    {
        if ($this->composerJson !== null) {
            return $this->composerJson;
        }

        if (($file = $this->getFile('composer.json')) !== null) {
            $this->composerJson = new Json($file->getPathname());
        }

        if ($this->composerJson === null) {
            foreach ($this->getFiles() as $file) {
                if ($file->getFilename() === 'composer.json') {
                    $this->composerJson = new Json($file->getPathname());
                    break;
                }
            }
        }

        if ($this->composerJson === null) {
            throw new FileNotFoundException('composer.json', $this->path);
        }

        $files = $this->composerJson->getAutoload()['files'] ?? [];

        foreach ($files as $file) {
            if (str_ends_with((string) $file, 'registration.php')) {
                $this->subFolder = (str_contains((string) $file, '/') ? trim(dirname((string) $file), "/") : '');
            }
        }

        return $this->composerJson;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Resolve class reference for a package file.
     */
    public function getClassReferenceByPath(FileInfo $file): string
    {
        $cacheKey = $file->getPathname();
        if (!isset($this->loadedFileClasses[$cacheKey])) {
            $this->loadedFileClasses[$cacheKey] = $this->fileClassScanner->getClassName($file);
        }

        return $this->loadedFileClasses[$cacheKey];
    }
}
