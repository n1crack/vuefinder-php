<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Exceptions\InvalidMethodException;
use Ozdemir\VueFinder\Contracts\ActionInterface;
use Ozdemir\VueFinder\Exceptions\ReadOnlyStorageException;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves and validates actions based on requests
 */
class ActionResolver
{
    private array $storageAdapters;

    public function __construct(array $storageAdapters)
    {
        $this->storageAdapters = $storageAdapters;
    }

    /**
     * Resolve and validate an action
     * 
     * @param Request $request
     * @param ActionFactory $actionFactory
     * @return ActionInterface
     */
    public function resolve(Request $request, ActionFactory $actionFactory): ActionInterface
    {
        // Use query parameter to determine action
        $query = $request->get('q');
        
        $routeArray = [
            'index' => 'get',
            'download' => 'get',
            'preview' => 'get',
            'search' => 'get',
            'create-folder' => 'post',
            'create-file' => 'post',
            'rename' => 'post',
            'move' => 'post',
            'copy' => 'post',
            'delete' => 'post',
            'upload' => 'post',
            'archive' => 'post',
            'unarchive' => 'post',
            'save' => 'post',
        ];

        if (!array_key_exists($query, $routeArray)
            || $routeArray[$query] !== strtolower($request->getMethod())) {
            throw new InvalidMethodException();
        }

        // Check if the storage is read-only
        $path = $request->get('path', '');
        $availableStorages = array_keys($this->storageAdapters);
        
        // Extract storage key from path
        $storageKey = null;
        if (preg_match('/^([^:]+):\/\//', $path, $matches)) {
            $storageKey = $matches[1];
        }
        
        if (!$storageKey || !in_array($storageKey, $availableStorages)) {
            $storageKey = $availableStorages[0] ?? null;
        }

        if ($storageKey && isset($this->storageAdapters[$storageKey])) {
            $storage = $this->storageAdapters[$storageKey];
            $readonly_array = ['index', 'download', 'preview', 'search'];

            if ($storage instanceof ReadOnlyFilesystemAdapter && !in_array($query, $readonly_array, true)) {
                throw new ReadOnlyStorageException();
            }
        }

        return $actionFactory->create($query);
    }
}

