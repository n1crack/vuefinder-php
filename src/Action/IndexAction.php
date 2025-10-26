<?php

namespace Ozdemir\VueFinder\Action;

use Ozdemir\VueFinder\Interface\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for listing directory contents
 */
class IndexAction extends BaseAction implements ActionInterface
{
    public function execute(): JsonResponse
    {
        $path = $this->request->get('path', '');
        $availableStorages = $this->storageResolver->getAvailableStorages();
        $currentStorageKey = $this->storageResolver->extractStorageFromPath($path, $availableStorages);
        $dirname = $path ?: $currentStorageKey . '://';

        $listContents = $this->filesystem->listContents($dirname);
        
        $files = array_merge(
            $this->filesystem->filterDirectories($listContents),
            $this->filesystem->filterFiles($listContents)
        );

        $files = $this->enrichNodes($files, $this->filesystem, $this->pathParser, $this->urlResolver);

        $read_only = false; // TODO: Check if current storage is read-only
        $storages = $this->getStorages();
        
        return new JsonResponse([
            'dirname' => $dirname,
            'files' => $files,
            'read_only' => $read_only,
            'storages' => $storages
        ]);
    }
}

