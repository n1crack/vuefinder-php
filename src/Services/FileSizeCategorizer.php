<?php

namespace Ozdemir\VueFinder\Services;

/**
 * Service for categorizing file sizes
 */
class FileSizeCategorizer
{
    private const SMALL_THRESHOLD = 1024 * 1024; // 1MB
    private const MEDIUM_THRESHOLD = 10 * 1024 * 1024; // 10MB

    /**
     * Categorize file size into small, medium, or large
     * 
     * @param int $size Size in bytes
     * @return string
     */
    public function categorize(int $size): string
    {
        if ($size <= self::SMALL_THRESHOLD) {
            return 'small';
        } elseif ($size <= self::MEDIUM_THRESHOLD) {
            return 'medium';
        } else {
            return 'large';
        }
    }

    /**
     * Filter files by size category
     * 
     * @param array $files
     * @param string $sizeFilter 'all' | 'small' | 'medium' | 'large'
     * @return array
     */
    public function filterBySize(array $files, string $sizeFilter = 'all'): array
    {
        if ($sizeFilter === 'all') {
            return $files;
        }

        return array_values(array_filter($files, function($file) use ($sizeFilter) {
            if ($file['type'] !== 'file' || !isset($file['file_size'])) {
                return false;
            }

            $fileSizeCategory = $this->categorize($file['file_size']);
            return $fileSizeCategory === $sizeFilter;
        }));
    }
}

