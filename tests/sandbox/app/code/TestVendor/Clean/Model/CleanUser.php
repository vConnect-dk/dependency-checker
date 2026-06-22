<?php
declare(strict_types=1);

namespace TestVendor\Clean\Model;

use TestVendor\Base\Model\SomeService;

class CleanUser
{
    public function __construct(private readonly SomeService $svc)
    {
    }

    public function work(): string
    {
        return $this->svc->doSomething();
    }
}
