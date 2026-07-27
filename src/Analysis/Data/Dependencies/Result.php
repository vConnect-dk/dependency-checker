<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Data\Dependencies;

use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;

class Result implements DefectiveResultInterface
{
    /**
     * @param string[] $composerDefects
     * @param string[] $moduleXmlDefects
     */
    public function __construct(
        private readonly string $packageName,
        private array $composerDefects,
        private array $moduleXmlDefects
    ) {
    }

    public function hasDefects(): bool
    {
        return !empty($this->composerDefects[DependencyInterface::TYPE_SOFT]) ||
            !empty($this->composerDefects[DependencyInterface::TYPE_HARD]) ||
            !empty($this->moduleXmlDefects[DependencyInterface::TYPE_EXPECTED]);
    }

    public function hasNotices(): bool
    {
        return !empty($this->composerDefects[DependencyInterface::TYPE_EXCESSIVE]) ||
            !empty($this->moduleXmlDefects[DependencyInterface::TYPE_EXCESSIVE]);
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * Return complex defects array.
     * Structure:
     * [
     *  'composer' => [
     *          'missed\module-one',
     *          'missed\module-two'
     *        ],
     *  'module' => [
     *          'Module_One',
     *          'Module_Two',
     *        ]
     * ]
     */
    public function getResult(): array
    {
        return [
            'composer' => $this->composerDefects,
            'module' => $this->moduleXmlDefects
        ];
    }
}
