<?php

namespace Ozdemir\VueFinder;

use Ozdemir\VueFinder\Contracts\FilesystemServiceInterface;
use Ozdemir\VueFinder\Contracts\PathParserInterface;
use Ozdemir\VueFinder\Contracts\StorageResolverInterface;
use Ozdemir\VueFinder\Contracts\UrlResolverInterface;

/**
 * VueFinder Core - Framework agnostic core functionality
 * Handles dependency management and action creation
 */
class VueFinderCore
{
    private StorageResolverInterface $storageResolver;
    private FilesystemServiceInterface $filesystem;
    private PathParserInterface $pathParser;
    private UrlResolverInterface $urlResolver;
    private array $config;

    public function __construct(
        StorageResolverInterface $storageResolver,
        FilesystemServiceInterface $filesystem,
        PathParserInterface $pathParser,
        UrlResolverInterface $urlResolver,
        array $config = []
    ) {
        $this->storageResolver = $storageResolver;
        $this->filesystem = $filesystem;
        $this->pathParser = $pathParser;
        $this->urlResolver = $urlResolver;
        $this->config = $config;
    }

    /**
     * Get storage resolver
     * 
     * @return StorageResolverInterface
     */
    public function getStorageResolver(): StorageResolverInterface
    {
        return $this->storageResolver;
    }

    /**
     * Get filesystem service
     * 
     * @return FilesystemServiceInterface
     */
    public function getFilesystem(): FilesystemServiceInterface
    {
        return $this->filesystem;
    }

    /**
     * Get path parser
     * 
     * @return PathParserInterface
     */
    public function getPathParser(): PathParserInterface
    {
        return $this->pathParser;
    }

    /**
     * Get URL resolver
     * 
     * @return UrlResolverInterface
     */
    public function getUrlResolver(): UrlResolverInterface
    {
        return $this->urlResolver;
    }

    /**
     * Get config
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get a specific config value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}

