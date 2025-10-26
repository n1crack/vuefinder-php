<?php

namespace Ozdemir\VueFinder;

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Ozdemir\VueFinder\Action\ActionFactory;
use Ozdemir\VueFinder\Action\ActionResolver;
use Ozdemir\VueFinder\Exception\VueFinderException;
use Ozdemir\VueFinder\Service\FilesystemService;
use Ozdemir\VueFinder\Service\PathParser;
use Ozdemir\VueFinder\Service\StorageResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Modern VueFinder implementation following SOLID principles
 */
class VueFinder
{
    private Request $request;
    private array $config;
    private array $storageAdapters;

    /**
     * VueFinder constructor.
     * 
     * @param array $storages Storage adapters configuration
     */
    public function __construct(array $storages)
    {
        $this->storageAdapters = $storages;
        $this->request = Request::createFromGlobals();
    }

    /**
     * Initialize and handle the request
     * 
     * @param array $config Configuration array
     * @return void
     */
    public function init(array $config): void
    {
        $this->config = $config;

        // Handle OPTIONS request for CORS
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', "*");
            $response->headers->set('Access-Control-Allow-Headers', "*");
            $response->send();
            return;
        }

        try {
            // Build dependencies
            $storageResolver = $this->buildStorageResolver();
            $filesystem = $this->buildFilesystem();
            $pathParser = new PathParser();
            
            // Create action factory and resolver
            $actionFactory = new ActionFactory(
                $this->request,
                $filesystem,
                $pathParser,
                $storageResolver,
                $this->config
            );
            
            $actionResolver = new ActionResolver($this->storageAdapters);
            
            // Resolve and execute action
            $action = $actionResolver->resolve($this->request, $actionFactory);
            $response = $action->execute();
            
        } catch (VueFinderException $e) {
            $response = new JsonResponse([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $response = new JsonResponse([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }

        // Set CORS headers
        $response->headers->set('Access-Control-Allow-Origin', "*");
        $response->headers->set('Access-Control-Allow-Headers', "*");

        $response->send();
    }

    /**
     * Build the storage resolver service
     * 
     * @return StorageResolver
     */
    private function buildStorageResolver(): StorageResolver
    {
        return new StorageResolver($this->storageAdapters);
    }

    /**
     * Build the filesystem service
     * 
     * @return FilesystemService
     */
    private function buildFilesystem(): FilesystemService
    {
        $storages = array_map(static fn($storage) => new Filesystem($storage), $this->storageAdapters);
        $manager = new MountManager($storages);
        
        return new FilesystemService($manager);
    }
}
