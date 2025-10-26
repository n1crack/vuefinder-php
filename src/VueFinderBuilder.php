<?php

namespace Ozdemir\VueFinder;

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Ozdemir\VueFinder\Service\FilesystemService;
use Ozdemir\VueFinder\Service\PathParser;
use Ozdemir\VueFinder\Service\StorageResolver;
use Ozdemir\VueFinder\Service\UrlResolver;

/**
 * Builder for VueFinder - automates all the setup!
 */
class VueFinderBuilder
{
    private array $storages;
    private array $config = [];

    /**
     * Set storage adapters
     * 
     * @param array $storages
     * @return self
     */
    public function withStorages(array $storages): self
    {
        $this->storages = $storages;
        return $this;
    }

    /**
     * Set configuration
     * 
     * @param array $config
     * @return self
     */
    public function withConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Build the VueFinder core
     * 
     * @return VueFinderCore
     */
    public function buildCore(): VueFinderCore
    {
        // Build all services automatically
        $storageResolver = new StorageResolver($this->storages);
        
        $storagesFS = array_map(static fn($storage) => new Filesystem($storage), $this->storages);
        $manager = new MountManager($storagesFS);
        $filesystem = new FilesystemService($manager);
        
        $pathParser = new PathParser();
        $urlResolver = new UrlResolver($this->config);

        return new VueFinderCore(
            $storageResolver,
            $filesystem,
            $pathParser,
            $urlResolver,
            $this->config
        );
    }

    /**
     * Build everything and return VueFinder (for simple usage)
     * 
     * @return VueFinder
     */
    public function build(): VueFinder
    {
        return new VueFinder($this->storages);
    }

    /**
     * Static factory method for quick setup
     * 
     * @param array $storages
     * @param array $config
     * @return VueFinderCore
     */
    public static function create(array $storages, array $config = []): VueFinderCore
    {
        return (new self())
            ->withStorages($storages)
            ->withConfig($config)
            ->buildCore();
    }
}

