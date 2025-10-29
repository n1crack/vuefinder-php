<?php

namespace Ozdemir\VueFinder\Contracts;

use League\Flysystem\StorageAttributes;
use League\Flysystem\FilesystemException;

/**
 * Interface for filesystem operations
 */
interface FilesystemServiceInterface
{
    /**
     * List contents of a directory
     * 
     * @param string $path
     * @param bool $deep
     * @return array
     * @throws FilesystemException
     */
    public function listContents(string $path, bool $deep = false): array;

    /**
     * Check if file or directory exists
     * 
     * @param string $path
     * @return bool
     * @throws FilesystemException
     */
    public function exists(string $path): bool;

    /**
     * Check if path is a file
     * 
     * @param string $path
     * @return bool
     * @throws FilesystemException
     */
    public function isFile(string $path): bool;

    /**
     * Check if path is a directory
     * 
     * @param string $path
     * @return bool
     * @throws FilesystemException
     */
    public function isDirectory(string $path): bool;

    /**
     * Get file size
     * 
     * @param string $path
     * @return int
     * @throws FilesystemException
     */
    public function fileSize(string $path): int;

    /**
     * Get MIME type
     * 
     * @param string $path
     * @return string
     * @throws FilesystemException
     */
    public function mimeType(string $path): string;

    /**
     * Read file as stream
     * 
     * @param string $path
     * @return resource
     * @throws FilesystemException
     */
    public function readStream(string $path);

    /**
     * Write stream to file
     * 
     * @param string $path
     * @param resource $stream
     * @return void
     * @throws FilesystemException
     */
    public function writeStream(string $path, $stream): void;

    /**
     * Write content to file
     * 
     * @param string $path
     * @param string $content
     * @return void
     * @throws FilesystemException
     */
    public function write(string $path, string $content): void;

    /**
     * Create directory
     * 
     * @param string $path
     * @return void
     * @throws FilesystemException
     */
    public function createDirectory(string $path): void;

    /**
     * Move file or directory
     * 
     * @param string $from
     * @param string $to
     * @return void
     * @throws FilesystemException
     */
    public function move(string $from, string $to): void;

    /**
     * Copy file or directory
     * 
     * @param string $from
     * @param string $to
     * @return void
     * @throws FilesystemException
     */
    public function copy(string $from, string $to): void;

    /**
     * Delete file
     * 
     * @param string $path
     * @return void
     * @throws FilesystemException
     */
    public function delete(string $path): void;

    /**
     * Delete directory
     * 
     * @param string $path
     * @return void
     * @throws FilesystemException
     */
    public function deleteDirectory(string $path): void;

    /**
     * Filter directories from file list
     * 
     * @param array $files
     * @return array
     */
    public function filterDirectories(array $files): array;

    /**
     * Filter files from file list
     * 
     * @param array $files
     * @param string|null $search
     * @return array
     */
    public function filterFiles(array $files, ?string $search = null): array;
}

