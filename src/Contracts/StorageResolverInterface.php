<?php

namespace Ozdemir\VueFinder\Contracts;

/**
 * Interface for resolving storage from path
 */
interface StorageResolverInterface
{
    /**
     * Extract storage key from path
     * 
     * @param string $path
     * @param array $availableStorages
     * @return string
     */
    public function extractStorageFromPath(string $path, array $availableStorages): string;

    /**
     * Get storage adapter by storage key
     * 
     * @param string $storageKey
     * @return object
     */
    public function getStorageAdapter(string $storageKey): object;

    /**
     * Get all available storage keys
     * 
     * @return array
     */
    public function getAvailableStorages(): array;
}

