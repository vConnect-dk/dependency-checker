<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Config\Queue;

class Topology
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

        if (!$this->source instanceof \DomDocument) {
            return;
        }

        foreach ($this->source->getElementsByTagName('exchange') as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }

            $node = [
                'name' => $element->getAttribute('name'),
                'type' => $element->getAttribute('type'),
                'connection' => $element->getAttribute('connection'),
                'durable' => $element->getAttribute('durable'),
                'autoDelete' => $element->getAttribute('autoDelete'),
                'internal' => $element->getAttribute('internal'),
                'binding' => []
            ];

            foreach ($element->getElementsByTagName('binding') as $binding) {
                if (!$binding instanceof \DOMElement) {
                    continue;
                }

                $node['binding'][] = [
                    'id' => $binding->getAttribute('id'),
                    'topic' => $binding->getAttribute('topic'),
                    'destinationType' => $binding->getAttribute('destinationType'),
                    'destination' => $binding->getAttribute('destination'),
                    'disabled' => $binding->getAttribute('disabled')
                ];
            }

            $this->content[] = $node;
        }

    }
}
