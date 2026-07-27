<?php

declare(strict_types=1);

namespace PHPSTORM_META
{
    use DI\Container;
    use DI\FactoryInterface;
    use Psr\Container\ContainerInterface;

    override(ContainerInterface::get(0), map([
        '' => '@',
    ]));
    override(Container::get(0), map([
        '' => '@',
    ]));
    override(FactoryInterface::make(0), map([
        '' => '@',
    ]));
}
