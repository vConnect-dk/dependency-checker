<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Domain\Scanner;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Application\Filesystem\Data\FileInfo;
use Vconnect\IntegrityChecker\Domain\Scanner\FileClassScanner;

class FileClassScannerTest extends TestCase
{
    public function testExtractsFullyQualifiedClassName(): void
    {
        $scanner = new FileClassScanner();
        $file = $this->fileInfoWithContents(<<<'PHP'
<?php
declare(strict_types=1);

namespace Foo\Bar;

class Baz
{
    public function x() {}
}
PHP);

        $this->assertSame('Foo\\Bar\\Baz', $scanner->getClassName($file));
    }

    public function testReturnsEmptyForFileWithoutClass(): void
    {
        $scanner = new FileClassScanner();
        $file = $this->fileInfoWithContents('<?php $x=1;');

        $this->assertSame('', $scanner->getClassName($file));
    }

    /**
     * In-memory FileInfo double: avoids temp-file I/O while keeping production FileInfo as the content boundary.
     */
    private function fileInfoWithContents(string $contents): FileInfo
    {
        return new class ($contents) extends FileInfo {
            public function __construct(
                private readonly string $inMemoryContents
            ) {
                parent::__construct(
                    fileName: 'Example.php',
                    pathname: '/virtual/Example.php',
                    basename: 'Example.php',
                    extension: 'php',
                    realPath: '/virtual/Example.php'
                );
            }

            public function getContents(): string
            {
                return $this->inMemoryContents;
            }
        };
    }
}
