<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Config\Queue;

class Publisher
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

        foreach ($this->source->getElementsByTagName('publisher') as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }
            $node = [
                'topic' => $element->getAttribute('name'),
                'disabled' => $element->getAttribute('disabled'),
                'connection' => []
            ];
            foreach ($element->getElementsByTagName('connection') as $connection) {
                if (!$connection instanceof \DOMElement) {
                    continue;
                }

                $node['connection'][] = [
                    'name' => $connection->getAttribute('name'),
                    'exchange' => $connection->getAttribute('exchange'),
                    'disabled' => $connection->getAttribute('disabled')
                ];
            }
            $this->content[] = $node;
        }
    }
}
