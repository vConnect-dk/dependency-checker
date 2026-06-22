<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Tests\Support\TestApplication;

/**
 * Shared setup for integration tests that analyse real sandbox Magento fixtures under app/code.
 *
 * Isolation: each test boots via {@see TestApplication::bootSandbox()}, which discards the
 * Application singleton and builds a fresh DI container. DirectoryRegistry static root is
 * reset/re-set in the same boot.
 */
abstract class SandboxIntegrationTestCase extends TestCase
{
    protected const FIXTURE_DEPENDENT = 'testvendor/dependent';
    protected const FIXTURE_BASE = 'testvendor/base';
    protected const FIXTURE_CLEAN = 'testvendor/clean';

    protected TestApplication $app;
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        try {
            $this->app = TestApplication::bootSandbox();
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $this->container = $this->app->container();
    }

    protected function tearDown(): void
    {
        TestApplication::shutdown();
    }

    protected function getFixturePackage(string $name): Package
    {
        try {
            return $this->app->package($name);
        } catch (\RuntimeException $e) {
            $this->fail(sprintf('Sandbox fixture package "%s" must be discoverable under app/code', $name));
        }
    }

    /**
     * @return list<string>
     */
    protected function hardDeps($result): array
    {
        return array_values($result->getHardDependencies());
    }

    /**
     * @return list<string>
     */
    protected function softDeps($result): array
    {
        return array_values($result->getSoftDependencies());
    }
}
