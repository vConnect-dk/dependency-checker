<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Scanner;

use Vconnect\IntegrityChecker\Application\Filesystem\Data\FileInfo;
use Vconnect\IntegrityChecker\Exception\InvalidFileException;

class FileClassScanner
{
    private const NAMESPACE_TOKENS = [
        T_WHITESPACE => true,
        T_STRING => true,
        T_NS_SEPARATOR => true,
        T_NAME_QUALIFIED => true,
        T_NAME_FULLY_QUALIFIED => true,
    ];

    private const ALLOWED_OPEN_BRACES_TOKENS = [
        T_CURLY_OPEN => true,
        T_DOLLAR_OPEN_CURLY_BRACES => true,
        T_STRING_VARNAME => true
    ];

    /**
     * Extracts the fully qualified class name from a file.
     *
     * File contents are read via {@see FileInfo::getContents()} so I/O stays at the FileInfo boundary
     * and unit tests can supply in-memory FileInfo doubles without touching the filesystem.
     */
    public function getClassName(FileInfo $file): string
    {
        $namespaceParts = [];
        $class = '';
        $triggerClass = false;
        $triggerNamespace = false;
        $braceLevel = 0;
        $bracedNamespace = false;

        // phpcs:ignore
        $tokens = token_get_all($file->getContents());
        foreach ($tokens as $index => $token) {
            $tokenIsArray = is_array($token);
            // Is either a literal brace or an interpolated brace with a variable
            if ($token === '{' || ($tokenIsArray && isset(self::ALLOWED_OPEN_BRACES_TOKENS[$token[0]]))) {
                $braceLevel++;
            } elseif ($token === '}') {
                $braceLevel--;
            }

            // The namespace keyword was found in the last loop
            if ($triggerNamespace) {
                // A string ; or a discovered namespace that looks like "namespace name { }"
                if (!$tokenIsArray || ($namespaceParts && $token[0] === T_WHITESPACE)) {
                    $triggerNamespace = false;
                    $namespaceParts[] = '\\';
                    continue;
                }

                $namespaceParts[] = $token[1];

                // `class` token is not used with a valid class name
            } elseif ($triggerClass && !$tokenIsArray) {
                $triggerClass = false;
                // The class keyword was found in the last loop
            } elseif ($triggerClass && $token[0] === T_STRING) {
                $triggerClass = false;
                $class = $token[1];
            }

            switch ($token[0]) {
                case T_NAMESPACE:
                    // Current loop contains the namespace keyword. Between this and the semicolon is the namespace
                    $triggerNamespace = true;
                    $namespaceParts = [];
                    $bracedNamespace = $this->isBracedNamespace($index, $tokens);
                    break;
                case T_CLASS:
                    // Current loop contains the class keyword. Next loop will have the class name itself.
                    if ($braceLevel === 0 || ($bracedNamespace && $braceLevel === 1)) {
                        $triggerClass = true;
                    }

                    break;
            }

            // We have a class name, let's concatenate and return it!
            if ($class !== '') {
                return trim(implode('', $namespaceParts)) . trim($class);
            }
        }

        return $class;
    }

    /**
     * Looks forward from the current index to determine if the namespace is nested in {} or terminated with ;
     */
    private function isBracedNamespace(int $index, array $tokens): bool
    {
        $len = count($tokens);
        while ($index++ < $len) {
            if (!is_array($tokens[$index])) {
                if ($tokens[$index] === ';') {
                    return false;
                }

                if ($tokens[$index] === '{') {
                    return true;
                }

                continue;
            }

            if (!isset(self::NAMESPACE_TOKENS[$tokens[$index][0]])) {
                throw new InvalidFileException('Namespace not defined properly');
            }
        }

        throw new InvalidFileException('Could not find namespace termination');
    }
}
