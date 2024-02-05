<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Data\TopologicalOrder;

use Vconnect\IntegrityChecker\Analysis\Data\ResultInterface;

class Result implements ResultInterface
{
    public function __construct(
        private readonly iterable $data
    ) {

    }

    public function getResult(): iterable
    {
        return $this->data;
    }
}
