<?php

namespace Ozdemir\VueFinder\Services;

use Ozdemir\VueFinder\Contracts\UrlResolverInterface;

/**
 * URL Resolver - determines public URLs for files
 */
class UrlResolver implements UrlResolverInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Resolve public URL for a file path
     * 
     * @param string $path File path
     * @return string|null
     */
    public function resolveUrl(string $path): ?string
    {
        // Method 1: Check for explicit public links mapping
        if (isset($this->config['publicLinks'])) {
            foreach ($this->config['publicLinks'] as $storagePath => $baseUrl) {
                if (strpos($path, $storagePath) === 0) {
                    return str_replace($storagePath, $baseUrl, $path);
                }
            }
        }

        // Method 2: Use storage-specific public base URL
        if (preg_match('/^([^:]+):\/\/(.*)$/', $path, $matches)) {
            $storageKey = $matches[1];
            $storagePath = $matches[2];
            
            // Check if this storage has a public base URL
            if (isset($this->config['storages'][$storageKey]['publicBaseUrl'])) {
                return $this->config['storages'][$storageKey]['publicBaseUrl'] . '/' . ltrim($storagePath, '/');
            }
        }

        // Method 3: Use app base URL + storage config
        $appUrl = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $this->config['appUrl'] ?? "$scheme://$appUrl";

        // Get storage key from path
        if (preg_match('/^([^:]+):\/\//', $path, $matches)) {
            $storageKey = $matches[1];
            $storagePath = str_replace("$storageKey://", "", $path);
            
            // Use storage public prefix
            $publicPrefix = $this->config['storages'][$storageKey]['publicPrefix'] ?? "storage/$storageKey";
            
            return "$baseUrl/$publicPrefix/" . ltrim($storagePath, '/');
        }

        return null;
    }

    /**
     * Check if a path should have a public URL
     * 
     * @param string $path
     * @return bool
     */
    public function shouldHavePublicUrl(string $path): bool
    {
        // Method 1: Check explicit exclusions
        if (isset($this->config['publicExclusions'])) {
            foreach ($this->config['publicExclusions'] as $exclusion) {
                if (strpos($path, $exclusion) === 0) {
                    return false;
                }
            }
        }

        // Method 2: Check if storage is marked as public
        if (preg_match('/^([^:]+):\/\//', $path, $matches)) {
            $storageKey = $matches[1];
            
            // Check if storage has public access disabled
            if (isset($this->config['storages'][$storageKey]['public']) && 
                $this->config['storages'][$storageKey]['public'] === false) {
                return false;
            }
        }

        // Method 3: Always generate URL if configured
        if (isset($this->config['publicLinks']) || isset($this->config['storages'])) {
            return true;
        }

        return false;
    }
}

