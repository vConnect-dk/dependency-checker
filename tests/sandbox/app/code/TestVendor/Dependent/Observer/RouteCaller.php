<?php
declare(strict_types=1);

namespace TestVendor\Dependent\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;

class RouteCaller implements ObserverInterface
{
    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function execute(Observer $observer)
    {
        // Routes scanner collects getUrl calls; RouteMapper resolves to module
        $url = $this->urlBuilder->getUrl('testvendor_base/base/index');
        // Use $url to avoid unused var issues in static analysis
        $_ = $url;
    }
}
