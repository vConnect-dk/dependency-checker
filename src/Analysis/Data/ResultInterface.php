<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Data;

interface ResultInterface
{
    /**
     * @return string[]
     */
    public function getResult(): iterable;
}
