<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Contracts\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for listing directory contents
 */
class ListAction extends BaseAction implements ActionInterface
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

        $read_only = $this->storageResolver->isReadOnly($currentStorageKey);
        $storages = $this->getStorages();

        /** @var mixed $responseData */
        $responseData = [
            'dirname' => $dirname,
            'files' => $files,
            'read_only' => $read_only,
            'storages' => $storages
        ];

        return new JsonResponse($responseData);
    }
}

