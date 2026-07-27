<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker;

use DI\ContainerBuilder;
use DI\FactoryInterface;
use Exception;
use Invoker\InvokerInterface;
use Psr\Container\ContainerInterface;

class Application
{
    private readonly ContainerInterface&FactoryInterface&InvokerInterface $container;
    private static ?self $instance = null;

    /**
     * @throws Exception
     */
    private function __construct()
    {
        $this->container = $this->buildContainer();
    }

    private function buildContainer(): ContainerInterface&FactoryInterface&InvokerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(__DIR__ . '/etc/di.php');
        return $containerBuilder->build();
    }

    public static function get(): self
    {
        if (!self::$instance instanceof Application) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getContainer(): ContainerInterface&FactoryInterface&InvokerInterface
    {
        return $this->container;
    }
}
