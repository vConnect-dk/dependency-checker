<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Filesystem\Data;

class FileInfo
{
    public function __construct(
        private readonly string $fileName,
        private readonly string $pathname,
        private readonly string $basename,
        private readonly string $extension,
        private readonly string $realPath
    ) {
    }

    public static function fromSplFileInfo(\SplFileInfo $fileInfo): self
    {
        return new self(
            fileName: $fileInfo->getFilename(),
            pathname: $fileInfo->getPathname(),
            basename: $fileInfo->getBasename(),
            extension: $fileInfo->getExtension(),
            realPath: $fileInfo->getRealPath()
        );
    }

    public function getContents(): string
    {
        $contents = file_get_contents($this->pathname);
        if ($contents === false) {
            throw new \RuntimeException("Failed to read file '{$this->pathname}'");
        }

        return $contents;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getBasename(?string $suffix = null): string
    {
        return $suffix === null ? $this->basename : basename($this->basename, $suffix);
    }

    public function getPathname(): string
    {
        return $this->pathname;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getRealPath(): string
    {
        return $this->realPath;
    }
}