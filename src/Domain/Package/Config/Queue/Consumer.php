<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Config\Queue;

class Consumer
{
    private ?array $content = null;

    public function __construct(
        private readonly ?\DomDocument $source
    ) {
    }

    public function getContent(): array
    {
        if ($this->content === null) {
            $this->parseContent();
        }

        return $this->content;
    }

    private function parseContent(): void
    {
        $this->content = [];

        if ($this->source === null) {
            return;
        }

        foreach ($this->source->getElementsByTagName('consumer') as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }
            $this->content[] = [
                'name' => $element->getAttribute('name'),
                'queue' => $element->getAttribute('queue'),
                'handler' => $element->getAttribute('handler'),
                'instance' => $element->getAttribute('consumerInstance'),
                'connection' => $element->getAttribute('connection'),
                'maxMessages' => $element->getAttribute('maxMessages'),
                'maxIdleTime' => $element->getAttribute('maxIdleTime'),
                'sleep' => $element->getAttribute('sleep'),
                'onlySpawnWhenMessageAvailable' => $element->getAttribute('onlySpawnWhenMessageAvailable')
            ];
        }
    }
}
