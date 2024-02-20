<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;

class DDLUsageRegexpAnalyzer
{
    private const REGEXP = /** @lang RegExp */
        '/->getTable(?:Name)?\(\s*[\'"](?<tableName>[a-zA-Z0-9_]+)[\'"]\s*\)/';

    public function getTablesUsed(\SplFileInfo $file): array
    {
        $contents = \php_strip_whitespace($file->getPathname());
        if (!preg_match_all(self::REGEXP, $contents, $matches)) {
            return [];
        }

        return $matches['tableName'] ?? [];
    }
}