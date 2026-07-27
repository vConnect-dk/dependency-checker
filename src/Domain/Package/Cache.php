<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package;

use RuntimeException;
use SplFileInfo;
use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\Package\Cache\ChecksumCalculator;

class Cache
{
    private ?SplFileInfo $cacheFile = null;

    public function __construct(
        private readonly ChecksumCalculator $checksumCalculator
    ) {
    }

    public function hasCache(): bool
    {
        return $this->getCacheFile()->isReadable();
    }

    /**
     * @param string $vendorPath
     * @return Package[]
     */
    public function loadCache(): array
    {
        if (!$this->hasCache()) {
            throw new RuntimeException('Cache is missing');
        }

        $cache = $this->getCacheFile();
        $cacheContent = $cache->openFile()->fread($cache->getSize());
        return unserialize(gzuncompress($cacheContent));
    }

    /**
     * @param Package[] $packages
     */
    public function save(array $packages): void
    {
        $this->clear();
        $cacheContent = gzcompress(serialize($packages));
        $cacheFile = $this->getCacheFile();
        $this->createCacheDirectory();
        $cacheFile->openFile('w')->fwrite($cacheContent);
    }

    public function clear(): void
    {
        $directory = $this->getCacheDir();
        if (is_dir($directory)) {
            $files = glob($directory . '*', GLOB_MARK);
            foreach ($files as $file) {
                unlink($file);
            }

            rmdir($directory);
        }
    }

    private function getCacheDir(): string
    {
        return sprintf('%s/var/cache/integrity-checker/', DirectoryRegistry::getRoot());
    }

    private function getCacheFile(): SplFileInfo
    {
        if (!$this->cacheFile instanceof SplFileInfo) {
            $path = $this->getCacheDir() . $this->checksumCalculator->getCheckSum();
            $this->cacheFile = new SplFileInfo($path);
        }

        return $this->cacheFile;
    }

    private function createCacheDirectory(): void
    {
        if (!is_dir($this->getCacheDir())) {
            mkdir($this->getCacheDir(), 0777, true);
        }
    }
}
