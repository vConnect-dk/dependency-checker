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
    private ContainerInterface&FactoryInterface&InvokerInterface $container;
    private static self $instance;

    /**
     * @throws Exception
     */
    private function __construct()
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder
            ->addDefinitions(__DIR__ . '/etc/di.php')
            ->addDefinitions(__DIR__ . '/etc/events.php');
        $this->container = $containerBuilder->build();
    }

    public static function get(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getContainer(): ContainerInterface&FactoryInterface&InvokerInterface
    {
        return $this->container;
    }
}
