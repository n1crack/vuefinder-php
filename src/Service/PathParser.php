<?php

namespace Ozdemir\VueFinder\Service;

use Ozdemir\VueFinder\Interface\PathParserInterface;

/**
 * Service for parsing and manipulating paths
 */
class PathParser implements PathParserInterface
{
    /**
     * Custom dirname function that properly handles storage:// paths
     * 
     * @param string $path
     * @return string
     */
    public function customDirname(string $path): string
    {
        // Handle storage:// format
        if (preg_match('/^([^:]+):\/\/(.*)$/', $path, $matches)) {
            $storage = $matches[1];
            $actualPath = $matches[2];
            
            // If path is empty or just '.', return storage root
            if (empty($actualPath) || $actualPath === '.') {
                return $storage . '://';
            }
            
            $dirname = dirname($actualPath);
            
            // If dirname returns '.' or empty, return storage root
            if ($dirname === '.' || $dirname === '') {
                return $storage . '://';
            }
            
            return $storage . '://' . $dirname;
        }
        
        // Fallback to regular dirname for non-storage paths
        return dirname($path);
    }

    /**
     * Normalize path
     * 
     * @param string $path
     * @return string
     */
    public function normalizePath(string $path): string
    {
        return trim($path);
    }

    /**
     * Validate filename
     * 
     * @param string $name
     * @return bool
     */
    public function isValidFilename(string $name): bool
    {
        return !empty($name) && strpbrk($name, "\\/?%*:|\"<>") === false;
    }
}

