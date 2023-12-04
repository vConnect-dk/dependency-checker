<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Config;

class DbSchema
{
    private array $content = [];

    public function __construct(?\DOMDocument $source = null)
    {
        if ($source) {
            $this->content = $this->recursiveConvert($this->getTablesNode($source));
        }
    }

    /**
     * Get Db Schema Package Content;
     *
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * We exactly know, that our schema is consists from tables.
     * So we do not need root elements in result, only table names.
     * So proposed to select only tables from all DOMDocument.
     *
     * @param \DOMDocument $element
     *
     * @return \DOMNodeList
     */
    private function getTablesNode(\DOMDocument $element): \DOMNodeList
    {
        return $element->getElementsByTagName('table');
    }

    /**
     * Convert elements.
     *
     * @param \Traversable $source
     *
     * @return array
     */
    private function recursiveConvert(\Traversable $source): array
    {
        $output = [];
        foreach ($source as $element) {
            if ($element instanceof \DOMElement) {
                $key = $this->getIdAttributeValue($element);

                if ($element->hasChildNodes()) {
                    $output[$element->tagName][$key] =
                        array_replace(
                            $this->recursiveConvert($element->childNodes),
                            $this->interpretAttributes($element)
                        );
                } elseif ($this->hasAttributesExceptIdAttribute($element)) {
                    $output[$element->tagName][$key] = $this->interpretAttributes($element);
                } else {
                    $output[$element->tagName][$key] = $key;
                }
            }
        }

        return $output;
    }

    /**
     * Provide the value of the ID attribute for each element.
     *
     * @param \DOMElement $element
     *
     * @return string
     */
    private function getIdAttributeValue(\DOMElement $element): string
    {
        $idAttributeValue = '';
        switch ($element->tagName) {
            case ('table'):
            case ('column'):
                $idAttributeValue = $element->getAttribute('name');
                break;
            case ('index'):
            case ('constraint'):
                $idAttributeValue = $element->getAttribute('referenceId');
                break;
        }

        return $idAttributeValue;
    }

    /**
     * Check whether we have any attributes except ID attribute.
     *
     * @param \DOMElement $element
     *
     * @return bool
     */
    private function hasAttributesExceptIdAttribute(\DOMElement $element)
    {
        return $element->hasAttribute('xsi:type') || $element->attributes->length >= 2;
    }

    /**
     * Mix attributes that comes from XML schema with default ones.
     *
     * So if you will not have some attribute in schema - it will be taken from default one.
     *
     * @param \DOMElement $domElement
     *
     * @return array
     */
    private function interpretAttributes(\DOMElement $domElement): array
    {
        $attributes = $this->getAttributes($domElement);
        $xsiType = $domElement->getAttribute('xsi:type');

        if ($xsiType) {
            $attributes['type'] = $xsiType;
        }

        return $attributes;
    }

    /**
     * Convert XML attributes into raw array with attributes.
     *
     * @param \DOMElement $element
     *
     * @return array
     */
    private function getAttributes(\DOMElement $element): array
    {
        $attributes = [];
        $attributeNodes = $element->attributes;

        /** @var \DOMAttr $attribute */
        foreach ($attributeNodes as $domAttr) {
            $attributes[$domAttr->name] = $domAttr->value;
        }

        return $attributes;
    }
}
