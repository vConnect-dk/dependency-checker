<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Config;

use SplFileInfo;
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


    /**
     * @param SplFileInfo|null $communication
     * @param SplFileInfo|null $consumer
     * @param SplFileInfo|null $publisher
     * @param SplFileInfo|null $topology
     */
    public function __construct(
        private readonly ?SplFileInfo $communication = null,
        private readonly ?SplFileInfo $consumer = null,
        private readonly ?SplFileInfo $publisher = null,
        private readonly ?SplFileInfo $topology = null
    ) {
    }

    public function getCommunication(): Communication
    {
        if ($this->communicationConfig) {
            return $this->communicationConfig;
        }

        $config = null;
        if ($this->communication && $this->communication->isReadable()) {
            $config = new \DOMDocument();
            $config->loadXML($this->communication->openFile()->fread($this->communication->getSize()));
        }
        $this->communicationConfig = new Communication($config);

        return $this->communicationConfig;
    }

    public function getConsumer(): Consumer
    {
        if ($this->consumerConfig) {
            return $this->consumerConfig;
        }

        $config = null;
        if ($this->consumer && $this->consumer->isReadable()) {
            $config = new \DOMDocument();
            $config->loadXML($this->consumer->openFile()->fread($this->consumer->getSize()));
        }
        $this->consumerConfig = new Consumer($config);

        return $this->consumerConfig;
    }

    public function getPublisher(): Publisher
    {
        if ($this->publisherConfig) {
            return $this->publisherConfig;
        }

        $config = null;
        if ($this->publisher && $this->publisher->isReadable()) {
            $config = new \DomDocument();
            $config->loadXML($this->publisher->openFile()->fread($this->publisher->getSize()));
        }
        $this->publisherConfig = new Publisher($config);

        return $this->publisherConfig;
    }

    public function getTopology(): Topology
    {
        if ($this->topologyConfig) {
            return $this->topologyConfig;
        }

        $config = null;
        if ($this->topology && $this->topology->isReadable()) {
            $config = new \DomDocument();
            $config->loadXML($this->topology->openFile()->fread($this->topology->getSize()));
        }

        $this->topologyConfig = new Topology($config);

        return $this->topologyConfig;
    }
}
