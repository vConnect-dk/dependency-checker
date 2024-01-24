<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Config\Queue;

class Communication
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

        foreach ($this->source->getElementsByTagName('topic') as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }
            $node = [
                'name' => $element->getAttribute('name'),
                'request' => $element->getAttribute('request'),
                'response' => $element->getAttribute('response'),
                'schema' => $element->getAttribute('schema'),
                'is_sync' => $element->getAttribute('is_synchronous'),
                'handlers' => []
            ];

            foreach ($element->getElementsByTagName('handler') as $handler) {
                if (!$handler instanceof \DOMElement) {
                    continue;
                }
                $node['handlers'][] = [
                    'name' => $handler->getAttribute('name'),
                    'type' => $handler->getAttribute('type'),
                    'method' => $handler->getAttribute('method'),
                    'disabled' => $handler->getAttribute('disabled')
                ];
            }
            $this->content[] = $node;
        }
    }
}
