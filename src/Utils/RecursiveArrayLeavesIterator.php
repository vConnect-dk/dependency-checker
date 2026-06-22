<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Utils;

/**
 * Iterator for recursively iterating tree leaves only
 */
class RecursiveArrayLeavesIterator extends \RecursiveIteratorIterator
{
    public function __construct(array $array)
    {
        parent::__construct(new ArrayOnlyIterator($array));
    }
}
