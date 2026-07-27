<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Config;

use Vconnect\IntegrityChecker\Application\Filesystem\Data\FileInfo;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue\Communication;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue\Consumer;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue\Publisher;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue\Topology;

class Queue
{
    private ?Communication $communicationConfig = null;
    private ?Consumer $consumerConfig = null;
    private ?Publisher $publisherConfig = null;
    private ?Topology $topologyConfig = null;


    public function __construct(
        private readonly ?FileInfo $communication = null,
        private readonly ?FileInfo $consumer = null,
        private readonly ?FileInfo $publisher = null,
        private readonly ?FileInfo $topology = null
    ) {
    }

    public function getCommunication(): Communication
    {
        if ($this->communicationConfig !== null) {
            return $this->communicationConfig;
        }

        $config = null;
        if ($this->communication !== null) {
            $config = new \DOMDocument();
            $config->loadXML($this->communication->getContents());
        }

        $this->communicationConfig = new Communication($config);

        return $this->communicationConfig;
    }

    public function getConsumer(): Consumer
    {
        if ($this->consumerConfig !== null) {
            return $this->consumerConfig;
        }

        $config = null;
        if ($this->consumer !== null) {
            $config = new \DOMDocument();
            $config->loadXML($this->consumer->getContents());
        }

        $this->consumerConfig = new Consumer($config);

        return $this->consumerConfig;
    }

    public function getPublisher(): Publisher
    {
        if ($this->publisherConfig !== null) {
            return $this->publisherConfig;
        }

        $config = null;
        if ($this->publisher !== null) {
            $config = new \DomDocument();
            $config->loadXML($this->publisher->getContents());
        }

        $this->publisherConfig = new Publisher($config);

        return $this->publisherConfig;
    }

    public function getTopology(): Topology
    {
        if ($this->topologyConfig !== null) {
            return $this->topologyConfig;
        }

        $config = null;
        if ($this->topology !== null) {
            $config = new \DomDocument();
            $config->loadXML($this->topology->getContents());
        }

        $this->topologyConfig = new Topology($config);

        return $this->topologyConfig;
    }
}
