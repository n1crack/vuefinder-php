<?php

namespace Ozdemir\VueFinder\Actions;

use League\Flysystem\FilesystemException;
use Ozdemir\VueFinder\Contracts\ActionInterface;
use Ozdemir\VueFinder\Exceptions\PathNotFoundException;
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

        // Validate path if provided
        if ($path) {
            // Check if path exists
            if (!$this->filesystem->exists($dirname)) {
                throw new PathNotFoundException('The specified path does not exist.');
            }

            // Check if path is a directory (not a file)
            if (!$this->filesystem->isDirectory($dirname)) {
                throw new PathNotFoundException('The specified path is not a directory.');
            }
        }

        // Try to list contents, catch any filesystem exceptions
        try {
            $listContents = $this->filesystem->listContents($dirname);
        } catch (FilesystemException $e) {
            throw new PathNotFoundException('Unable to list directory contents: ' . $e->getMessage());
        }
        
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

