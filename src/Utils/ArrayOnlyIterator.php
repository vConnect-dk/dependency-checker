<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Utils;

class ArrayOnlyIterator extends \RecursiveArrayIterator
{
    public function hasChildren(): bool
    {
        return parent::hasChildren() && is_array($this->current());
    }
}