<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

class Application
{
    private ContainerInterface $container;
    private static self $instance;
    private function __construct() {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->addDefinitions(__DIR__ . '/etc/di.php'); // There might be some DI configurations
        $this->container = $containerBuilder->build();
    }

    public static function get(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}