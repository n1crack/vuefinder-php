<?php

namespace Ozdemir\VueFinder\Services;

use League\Flysystem\Filesystem;
use Ozdemir\VueFinder\Contracts\StorageResolverInterface;

/**
 * Service for resolving storage adapters from paths
 */
class StorageResolver implements StorageResolverInterface
{
    private array $storageAdapters;
    private array $availableStorages;

    public function __construct(array $storageAdapters)
    {
        $this->storageAdapters = $storageAdapters;
        $this->availableStorages = array_keys($storageAdapters);
    }

    /**
     * Extract storage key from path
     * 
     * @param string $path
     * @param array $availableStorages
     * @return string
     */
    public function extractStorageFromPath(string $path, array $availableStorages): string
    {
        // If path contains storage:// format, extract the storage key
        if (preg_match('/^([^:]+):\/\//', $path, $matches)) {
            $storageKey = $matches[1];
            if (in_array($storageKey, $availableStorages, true)) {
                return $storageKey;
            }
        }
        
        // Default to first available storage if no valid storage found in path
        return $availableStorages[0] ?? '';
    }

    /**
     * Get storage adapter by storage key
     * 
     * @param string $storageKey
     * @return object
     */
    public function getStorageAdapter(string $storageKey): object
    {
        if (!isset($this->storageAdapters[$storageKey])) {
            throw new \InvalidArgumentException("Storage key '{$storageKey}' not found.");
        }
        
        return $this->storageAdapters[$storageKey];
    }

    /**
     * Get all available storage keys
     * 
     * @return array
     */
    public function getAvailableStorages(): array
    {
        return $this->availableStorages;
    }
}

