<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Data\Dependencies;

use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;

class Result implements DefectiveResultInterface
{
    private string $packageName;

    private array $composerDefects;

    private array $moduleXmlDefects;

    /**
     * @param string $packageName
     * @param string[] $composerDefects
     * @param string[] $moduleXmlDefects
     */
    public function __construct(string $packageName, array $composerDefects, array $moduleXmlDefects)
    {
        $this->packageName = $packageName;
        $this->composerDefects = $composerDefects;
        $this->moduleXmlDefects = $moduleXmlDefects;
    }

    /**
     * @return bool
     */
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

    /**
     * @return string
     */
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
     *
     * @return array
     */
    public function getResult(): array
    {
        return [
            'composer' => $this->composerDefects,
            'module' => $this->moduleXmlDefects
        ];
    }
}
