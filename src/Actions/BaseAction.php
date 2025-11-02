<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Contracts\FilesystemServiceInterface;
use Ozdemir\VueFinder\Contracts\PathParserInterface;
use Ozdemir\VueFinder\Contracts\StorageResolverInterface;
use Ozdemir\VueFinder\Contracts\UrlResolverInterface;
use Ozdemir\VueFinder\Traits\EnrichesNodeTrait;
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
    protected UrlResolverInterface $urlResolver;
    protected array $config;

    public function __construct(
        Request $request,
        FilesystemServiceInterface $filesystem,
        PathParserInterface $pathParser,
        StorageResolverInterface $storageResolver,
        UrlResolverInterface $urlResolver,
        array $config
    ) {
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->pathParser = $pathParser;
        $this->storageResolver = $storageResolver;
        $this->urlResolver = $urlResolver;
        $this->config = $config;
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

