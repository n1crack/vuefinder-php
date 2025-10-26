<?php

namespace Ozdemir\VueFinder\Interface;

/**
 * Interface for resolving public URLs
 */
interface UrlResolverInterface
{
    /**
     * Resolve public URL for a file path
     * 
     * @param string $path File path
     * @return string|null Public URL or null if not publicly accessible
     */
    public function resolveUrl(string $path): ?string;

    /**
     * Check if a path should have a public URL
     * 
     * @param string $path File path
     * @return bool
     */
    public function shouldHavePublicUrl(string $path): bool;
}

