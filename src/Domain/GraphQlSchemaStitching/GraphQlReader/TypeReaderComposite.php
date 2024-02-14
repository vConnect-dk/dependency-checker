<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\GraphQlSchemaStitching\GraphQlReader;

use GraphQL\Type\Definition\Type;

/**
 * Composite configured class used to determine which reader should be used for a specific type
 */
class TypeReaderComposite implements TypeMetaReaderInterface
{
    /** @var TypeMetaReaderInterface[] */
    private $typeReaders = [];

    /**
     * @param array $typeReaders
     */
    public function __construct(
        array $typeReaders = []
    ) {
        $this->typeReaders = $typeReaders;
    }


    /**
     * @inheritDoc
     */
    public function read(Type $typeMeta) : array
    {
        foreach ($this->typeReaders as $typeReader) {
            $result = $typeReader->read($typeMeta);
            if (!empty($result)) {
                return $result;
            }
        }
        return [];
    }
}
