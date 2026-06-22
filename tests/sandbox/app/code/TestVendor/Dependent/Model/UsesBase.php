<?php
declare(strict_types=1);

namespace TestVendor\Dependent\Model;

use TestVendor\Base\Model\SomeService;

class UsesBase
{
    public function __construct(
        private readonly SomeService $baseService
    ) {
    }

    public function run(): string
    {
        // Direct class usage should be picked as hard dependency
        return $this->baseService->doSomething();
    }
}
