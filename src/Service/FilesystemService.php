<?php

namespace Ozdemir\VueFinder\Service;

use League\Flysystem\MountManager;
use League\Flysystem\StorageAttributes;
use Ozdemir\VueFinder\Interface\FilesystemServiceInterface;

/**
 * Service for filesystem operations
 */
class FilesystemService implements FilesystemServiceInterface
{
    private MountManager $manager;

    public function __construct(MountManager $manager)
    {
        $this->manager = $manager;
    }

    public function listContents(string $path, bool $deep = false): array
    {
        return $this->manager
            ->listContents($path)
            ->map(fn(StorageAttributes $attributes) => $attributes->jsonSerialize())
            ->toArray();
    }

    public function exists(string $path): bool
    {
        return $this->manager->fileExists($path) || $this->manager->directoryExists($path);
    }

    public function isFile(string $path): bool
    {
        return $this->manager->fileExists($path);
    }

    public function isDirectory(string $path): bool
    {
        return $this->manager->directoryExists($path);
    }

    public function fileSize(string $path): int
    {
        return $this->manager->fileSize($path);
    }

    public function mimeType(string $path): string
    {
        try {
            return $this->manager->mimeType($path);
        } catch (\Exception $e) {
            return 'application/octet-stream';
        }
    }

    public function readStream(string $path)
    {
        return $this->manager->readStream($path);
    }

    public function writeStream(string $path, $stream): void
    {
        $this->manager->writeStream($path, $stream);
    }

    public function write(string $path, string $content): void
    {
        $this->manager->write($path, $content);
    }

    public function createDirectory(string $path): void
    {
        $this->manager->createDirectory($path);
    }

    public function move(string $from, string $to): void
    {
        $this->manager->move($from, $to);
    }

    public function copy(string $from, string $to): void
    {
        $this->manager->copy($from, $to);
    }

    public function delete(string $path): void
    {
        $this->manager->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->manager->deleteDirectory($path);
    }

    public function filterDirectories(array $files): array
    {
        return array_filter($files, static fn($item) => $item['type'] == 'dir');
    }

    public function filterFiles(array $files, ?string $search = null): array
    {
        return array_filter(
            $files,
            static fn($item) => $item['type'] == 'file' && (!$search || fnmatch("*$search*", $item['path'], FNM_CASEFOLD))
        );
    }
}

