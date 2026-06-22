<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Support;

use Psr\Container\ContainerInterface;
use Vconnect\IntegrityChecker\Application;
use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

/**
 * Test composition root / harness for unit & integration tests.
 *
 * Production {@see Application} stays minimal (get / getContainer only). This class owns sandbox
 * boot, container access, and isolation by discarding the process singleton so the next
 * {@see Application::get()} builds a fresh DI container.
 *
 * System tests should still invoke real bin/* entry points (real bootstrap.php / App()).
 */
final class TestApplication
{
    private static ?string $sandboxRoot = null;

    private function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * Boot (or re-boot) with a fresh application singleton and container.
     */
    public static function boot(?string $magentoRoot = null): self
    {
        self::discardApplicationSingleton();

        if ($magentoRoot !== null) {
            DirectoryRegistry::reset();
            DirectoryRegistry::setRoot(rtrim($magentoRoot, '/\\') . '/');
        }

        return new self(Application::get()->getContainer());
    }

    /**
     * Boot against tests/sandbox Magento tree.
     */
    public static function bootSandbox(): self
    {
        return self::boot(self::sandboxRoot());
    }

    public static function sandboxRoot(): string
    {
        if (self::$sandboxRoot === null) {
            $root = realpath(__DIR__ . '/../../sandbox');
            if ($root === false || !is_dir($root)) {
                throw new \RuntimeException('tests/sandbox is not available');
            }
            self::$sandboxRoot = $root;
        }

        return self::$sandboxRoot;
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function package(string $name): Package
    {
        /** @var PackagesRegistry $registry */
        $registry = $this->container->get(PackagesRegistry::class);

        foreach ($registry->getAllPackages() as $package) {
            if ($package->getName() === $name) {
                return $package;
            }
        }

        throw new \RuntimeException(sprintf('Package "%s" not found in current Magento root', $name));
    }

    /**
     * Tear down process-wide singletons/statics used by the tool.
     */
    public static function shutdown(): void
    {
        self::discardApplicationSingleton();
        DirectoryRegistry::reset();
    }

    /**
     * {@see Application} keeps a private static singleton with no public reset — tests clear it
     * here so production API does not grow test-only methods.
     */
    private static function discardApplicationSingleton(): void
    {
        $property = new \ReflectionProperty(Application::class, 'instance');
        $property->setValue(null, null);
    }
}
