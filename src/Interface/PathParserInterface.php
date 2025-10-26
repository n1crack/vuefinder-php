<?php

namespace Ozdemir\VueFinder\Interface;

/**
 * Interface for path parsing operations
 */
interface PathParserInterface
{
    /**
     * Custom dirname function that properly handles storage:// paths
     * 
     * @param string $path
     * @return string
     */
    public function customDirname(string $path): string;

    /**
     * Normalize path
     * 
     * @param string $path
     * @return string
     */
    public function normalizePath(string $path): string;
}

