<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package;

class LoaderChain implements LoaderInterface
{
    /**
     * @param LoaderInterface[] $loaders
     */
    public function __construct(
        private readonly array $loaders,
    ) {
    }

    public function loadPackages(): array
    {
        return array_reduce(
            $this->loaders,
            fn ($packages, LoaderInterface $loader): array => $packages + $loader->loadPackages(),
            []
        );
    }
}
