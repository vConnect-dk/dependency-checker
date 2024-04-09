<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Composer;

class Json
{
    private string $path;

    private ?array $content = null;

    private ?string $registrationPhp = null;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Get module directory path.
     *
     * @return string
     */
    public function getDirPath(): string
    {
        return dirname($this->path);
    }

    /**
     * Get Package Name
     *
     * @return string|null
     */
    public function getPackageName(): ?string
    {
        return $this->getContent()['name'] ?? null;
    }

    /**
     * Get Package Type (e.g. library, magento2-module etc).
     *
     * @return string|null
     */
    public function getPackageType(): ?string
    {
        return $this->getContent()['type'] ?? null;
    }

    /**
     * Get psr-4 package namespaces. Some packages could declare more than one namespace.
     *
     * @return array
     */
    public function getNamespaces(): array
    {
        return array_keys($this->getNamespacesFolders());
    }

    /**
     * Return packages specified in 'require' section.
     *
     * @return array
     */
    public function getRequire(): array
    {
        $dependencies = $this->getContent()['require'] ?? [];

        return $this->filterComposerPackages($dependencies);
    }

    public function getRequireDev(): array
    {
        $dependencies = $this->getContent()['require-dev'] ?? [];

        return $this->filterComposerPackages($dependencies);
    }

    /**
     * Return packages specified in 'suggest' section.
     *
     * @return array
     */
    public function getSuggest(): array
    {
        $dependencies = $this->getContent()['suggest'] ?? [];

        return $this->filterComposerPackages($dependencies);

    }

    public function getReplace(): array
    {
        $dependencies = $this->getContent()['replace'] ?? [];

        return $this->filterComposerPackages($dependencies);
    }

    /**
     * @param array $dependencies
     * @return array
     */
    private function filterComposerPackages(array $dependencies): array
    {
        $dependencies = array_keys($dependencies);
        return array_filter($dependencies, function (string $dependency): bool {
            return str_contains($dependency, '/');
        });
    }

    /**
     * Get composer.json content structured into array.
     *
     * @return array
     */
    private function getContent(): array
    {
        if (!$this->content) {
            $parsedContent = json_decode(file_get_contents($this->path), true);
            $this->content = $parsedContent ?? [];
        }

        return $this->content;
    }

    /**
     * Get Autoload PSR-4 section.
     *
     * @return array
     */
    public function getNamespacesFolders(): array
    {
        return $this->getContent()['autoload']['psr-4'] ?? [];
    }
}
