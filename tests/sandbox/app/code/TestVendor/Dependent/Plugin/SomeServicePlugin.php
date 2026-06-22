<?php
declare(strict_types=1);

namespace TestVendor\Dependent\Plugin;

use TestVendor\Base\Model\SomeService;

class SomeServicePlugin
{
    public function afterDoSomething(SomeService $subject, $result)
    {
        return $result . ' | plugin';
    }
}
