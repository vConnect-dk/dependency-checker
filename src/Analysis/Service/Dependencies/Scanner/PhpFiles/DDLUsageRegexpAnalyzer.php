<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;

class DDLUsageRegexpAnalyzer
{
    private const REGEXP = /** @lang RegExp */
        '/->getTable(?:Name)?\(\s*[\'"](?<tableName>\w+)[\'"]\s*\)/';

    public function getTablesUsed(string $fileContent): array
    {
        if (!preg_match_all(self::REGEXP, $fileContent, $matches)) {
            return [];
        }

        return $matches['tableName'] ?? [];
    }
}
