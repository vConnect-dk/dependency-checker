<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Composer;

class Json
{
    private ?array $content = null;

    public function __construct(private readonly string $path)
    {
    }

    /**
     * Get module directory path.
     */
    public function getDirPath(): string
    {
        return dirname($this->path);
    }

    /**
     * Get Package Name
     */
    public function getPackageName(): ?string
    {
        return $this->getContent()['name'] ?? null;
    }

    /**
     * Get Package Type (e.g. library, magento2-module etc).
     */
    public function getPackageType(): ?string
    {
        return $this->getContent()['type'] ?? null;
    }

    /**
     * Get psr-4 package namespaces. Some packages could declare more than one namespace.
     * //@TODO add support of PSR-0
     */
    public function getNamespaces(): array
    {
        return array_keys($this->getAutoload()['psr-4'] ?? []);
    }

    /**
     * Return packages specified in 'require' section.
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

    private function filterComposerPackages(array $dependencies): array
    {
        $dependencies = array_keys($dependencies);
        return array_filter($dependencies, fn (string $dependency): bool => str_contains($dependency, '/'));
    }

    /**
     * Get composer.json content structured into array.
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
     * Get Autoload section.
     */
    public function getAutoload(): array
    {
        return $this->getContent()['autoload'] ?? [];
    }
}
