<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder;

use Vconnect\IntegrityChecker\Domain\Package;

class WhitelistProcessor
{
    /**
     * @param string[] $whitelist
     * @param Package[] $packages
     * @return string[]
     */
    public function process(array $whitelist, iterable $packages): array
    {
        [$plainPackages, $wildcards] = $this->splitWhitelist($whitelist);
        foreach ($packages as $package) {
            $name = $package->getName();
            if (isset($plainPackages[$name])) {
                $plainPackages[$name] = $name;
            }
            foreach ($wildcards as $wildcard) {
                if (preg_match($wildcard, $name)) {
                    $plainPackages[$name] = $name;
                }
            }
        }

        return $plainPackages;
    }

    private function splitWhitelist(array $whitelist): array
    {
        $plainPackages = [];
        $wildcardItems = [];

        foreach ($whitelist as $key => $index) {
            if (str_contains((string) $key, '*')) {
                $wildcardItems[] = $this->buildRegexpFromWildcard($key);
            } else {
                $plainPackages[$key] = $index;
            }
        }

        return [$plainPackages, $wildcardItems];
    }

    private function buildRegexpFromWildcard(string $key): string
    {
        return '#' . str_replace('*', '.*', $key) . '#';
    }
}
