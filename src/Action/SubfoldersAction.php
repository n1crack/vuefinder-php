<?php

namespace Ozdemir\VueFinder\Action;

use Ozdemir\VueFinder\Interface\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for listing subfolders
 */
class SubfoldersAction extends BaseAction implements ActionInterface
{
    public function execute(): JsonResponse
    {
        $path = $this->request->get('path', '');
        $availableStorages = $this->storageResolver->getAvailableStorages();
        $currentStorageKey = $this->storageResolver->extractStorageFromPath($path, $availableStorages);
        $dirname = $path ?: $currentStorageKey . '://';

        $listContents = $this->filesystem->listContents($dirname);
        $folders = $this->filesystem->filterDirectories($listContents);

        $folders = array_map(function($folder) {
            return [
                'path' => $folder['path'],
                'basename' => basename($folder['path']),
            ];
        }, array_values($folders));

        $storages = $this->getStorages();
        
        return new JsonResponse([
            'folders' => $folders,
            'storages' => $storages
        ]);
    }
}

