<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain;

use Exception;

class FileClassScanner
{
    private const NAMESPACE_TOKENS = [
        T_WHITESPACE => true,
        T_STRING => true,
        T_NS_SEPARATOR => true
    ];

    private const ALLOWED_OPEN_BRACES_TOKENS = [
        T_CURLY_OPEN => true,
        T_DOLLAR_OPEN_CURLY_BRACES => true,
        T_STRING_VARNAME => true
    ];

    private static ?FileClassScanner $instance = null;

    /**
     * The filename of the file to introspect
     */
    private string $filename;

    private array $tokens;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Provide singleton instance of FileClassScanner.
     *
     * @return FileClassScanner
     */
    public static function getInstance(): FileClassScanner
    {
        if (!self::$instance) {
            self::$instance = new FileClassScanner();
        }

        return self::$instance;
    }

    /**
     * @param $filename
     * @return string
     * @throws Exception
     */
    public function getClassName($filename): string
    {
        // phpcs:ignore
        $filename = realpath($filename);
        // phpcs:ignore
        if (!file_exists($filename) || !\is_file($filename)) {
            throw new Exception(sprintf('File not found: %i', $filename));
        }
        $this->filename = $filename;

        return $this->extract();
    }

    /**
     * Extracts the fully qualified class name from a file.
     *
     * It only searches for the first match and stops looking as soon as it enters the class definition itself.
     *
     * Warnings are suppressed for this method due to a micro-optimization that only really shows up when this logic
     * is called several millions of times, which can happen quite easily with even moderately sized codebases.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @return string
     * @throws Exception
     */
    private function extract(): string
    {
        $namespaceParts = [];
        $class = '';
        $triggerClass = false;
        $triggerNamespace = false;
        $braceLevel = 0;
        $bracedNamespace = false;

        // phpcs:ignore
        $this->tokens = token_get_all($this->getFileContents());
        foreach ($this->tokens as $index => $token) {
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
                    $bracedNamespace = $this->isBracedNamespace($index);
                    break;
                case T_CLASS:
                    // Current loop contains the class keyword. Next loop will have the class name itself.
                    if ($braceLevel == 0 || ($bracedNamespace && $braceLevel == 1)) {
                        $triggerClass = true;
                    }
                    break;
            }

            // We have a class name, let's concatenate and return it!
            if ($class !== '') {
                $fqClassName = trim(join('', $namespaceParts)) . trim($class);
                return $fqClassName;
            }
        }
        return $class;
    }

    /**
     * Retrieves the contents of a file.  Mostly here for Mock injection
     *
     * @return string
     */
    private function getFileContents(): string
    {
        // phpcs:ignore
        return file_get_contents($this->filename);
    }

    /**
     * Looks forward from the current index to determine if the namespace is nested in {} or terminated with ;
     *
     * @param int $index
     * @return bool
     * @throws Exception
     */
    private function isBracedNamespace(int $index): bool
    {
        $len = count($this->tokens);
        while ($index++ < $len) {
            if (!is_array($this->tokens[$index])) {
                if ($this->tokens[$index] === ';') {
                    return false;
                } elseif ($this->tokens[$index] === '{') {
                    return true;
                }
                continue;
            }

            if (!isset(self::NAMESPACE_TOKENS[$this->tokens[$index][0]])) {
                throw new Exception('Namespace not defined properly');
            }
        }
        throw new Exception('Could not find namespace termination');
    }
}
