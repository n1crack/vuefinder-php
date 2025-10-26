<?php

namespace Ozdemir\VueFinder\Action;

use Ozdemir\VueFinder\Interface\FilesystemServiceInterface;
use Ozdemir\VueFinder\Interface\PathParserInterface;
use Ozdemir\VueFinder\Interface\StorageResolverInterface;
use Ozdemir\VueFinder\Trait\EnrichesNodeTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base action class with common functionality
 */
abstract class BaseAction
{
    use EnrichesNodeTrait;

    protected Request $request;
    protected FilesystemServiceInterface $filesystem;
    protected PathParserInterface $pathParser;
    protected StorageResolverInterface $storageResolver;
    protected array $config;

    public function __construct(
        Request $request,
        FilesystemServiceInterface $filesystem,
        PathParserInterface $pathParser,
        StorageResolverInterface $storageResolver,
        array $config
    ) {
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->pathParser = $pathParser;
        $this->storageResolver = $storageResolver;
        $this->config = $config;
    }

    /**
     * Get public links configuration
     * 
     * @return array|null
     */
    protected function getPublicLinks(): ?array
    {
        return $this->config['publicLinks'] ?? null;
    }

    /**
     * Get available storages
     * 
     * @return array
     */
    protected function getStorages(): array
    {
        return $this->storageResolver->getAvailableStorages();
    }
}

