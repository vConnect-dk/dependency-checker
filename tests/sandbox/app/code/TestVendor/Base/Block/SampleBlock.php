<?php
declare(strict_types=1);

namespace TestVendor\Base\Block;

use Magento\Framework\View\Element\Template;

class SampleBlock extends Template
{
    public function getGreeting(): string
    {
        return 'Hello from Base';
    }
}
