<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig;

use Vconnect\IntegrityChecker\Domain\Package\Config\Queue;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue\Communication;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue\Consumer;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue\Publisher;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue\Topology;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class ConfigAnalysis
{
    private const AMPQ_EXTENSION = 'magento/module-amqp';
    private const MYSQLMQ_EXTENSION = 'magento/module-mysql-mq';

    public function __construct(
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    public function analyzeConfigFiles(Queue $config, string $packageName): array
    {
        return array_unique(
            array_merge(
                $this->analyzeCommunication($config->getCommunication(), $packageName),
                $this->analyzeConsumers($config->getConsumer(), $packageName),
                $this->analyzePublisher($config->getPublisher()),
            //  $this->analyzeTopology($config->getTopology())
            )
        );
    }

    private function getDependencyByType(string $typeName): ?string
    {
        if (!str_contains($typeName, '\\')) {
            return null;
        }

        if (str_contains($typeName, '::')) {
            $typeName = explode('::', $typeName)[0];
        }

        $namespace = $this->packagesRegistry->getRealPackageNamespace($typeName);

        return $namespace ? $this->packagesRegistry->getPackageNameByNamespace($namespace) : null;
    }

    private function analyzeCommunication(Communication $communication, string $packageName): array
    {
        $content = $communication->getContent();
        $dependencies = [];
        if (empty($content)) {
            return $dependencies;
        }
        foreach ($content as $topic) {
            foreach (['response', 'request', 'schema'] as $attribute) {
                if (!$topic[$attribute]) {
                    continue;
                }

                $dep = $this->getDependencyByType($topic[$attribute]);
                if ($dep && $dep !== $packageName) {
                    $dependencies[] = $dep;
                }
            }

            foreach ($topic['handlers'] as $handler) {
                if (!$handler['type'] || $handler['disabled'] === 'true') {
                    continue;
                }
                $dep = $this->getDependencyByType($handler['type']);

                if ($dep && $dep !== $packageName) {
                    $dependencies[] = $dep;
                }
            }
        }

        return $dependencies;
    }

    private function analyzeConsumers(Consumer $consumer, string $packageName): array
    {
        $content = $consumer->getContent();
        $dependencies = [];

        if (empty($content)) {
            return $dependencies;
        }

        foreach ($content as $consumer) {
            foreach (['instance', 'handler'] as $attribute) {
                if (!$consumer[$attribute]) {
                    continue;
                }

                $dep = $this->getDependencyByType($consumer[$attribute]);
                if ($dep && $dep !== $packageName) {
                    $dependencies[] = $dep;
                }
            }

            if ($consumer['connection'] === 'amqp') {
                $dependencies[] = self::AMPQ_EXTENSION;
            }

            if ($consumer['connection'] === 'db') {
                $dependencies[] = self::MYSQLMQ_EXTENSION;
            }
        }

        return $dependencies;
    }

    private function analyzePublisher(Publisher $publisher): array
    {
        $content = $publisher->getContent();
        $dependencies = [];

        if (empty($content)) {
            return $dependencies;
        }

        foreach ($content as $publisher) {
            foreach ($publisher['connection'] as $connection) {
                if ($connection['disabled'] === "true") {
                    continue;
                }

                if ($connection['name'] === 'amqp') {
                    $dependencies[] = self::AMPQ_EXTENSION;
                }

                if ($connection['name'] === 'db') {
                    $dependencies[] = self::MYSQLMQ_EXTENSION;
                }
            }
        }

        return $dependencies;
    }

    // Placeholder for bright future

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @noinspection PhpUnusedParameterInspection
     */
    private function analyzeTopology(Topology $topology): array
    {
        return [];
    }
}
