<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Contracts\ActionInterface;
use Ozdemir\VueFinder\Services\FileSizeCategorizer;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for searching files
 */
class SearchAction extends BaseAction implements ActionInterface
{
    private FileSizeCategorizer $fileSizeCategorizer;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->fileSizeCategorizer = new FileSizeCategorizer();
    }

    public function execute(): JsonResponse
    {
        $size = $this->request->get('size'); // 'all' | 'small' | 'medium' | 'large'
        $path = $this->request->get('path', '');
        $deep = $this->request->get('deep', false);
        $filter = $this->request->get('filter');

        $availableStorages = $this->storageResolver->getAvailableStorages();
        $currentStorageKey = $this->storageResolver->extractStorageFromPath($path, $availableStorages);
        $dirname = $path ?: $currentStorageKey . '://';

        $listContents = $this->filesystem->listContents($dirname, $deep);
        $files = array_values($this->filesystem->filterFiles($listContents, $filter));

        $files = $this->enrichNodes($files, $this->filesystem, $this->pathParser, $this->urlResolver);

        // Add directory info for files
        $files = array_map(function($node) {
            $node['dir'] = $this->pathParser->customDirname($node['path']);
            return $node;
        }, $files);

        // Apply size filtering if size parameter is provided
        if ($size && in_array($size, ['small', 'medium', 'large', 'all'])) {
            $files = $this->fileSizeCategorizer->filterBySize($files, $size);
        }

        $storages = $this->getStorages();
        
        return new JsonResponse([
            'dirname' => $dirname,
            'files' => $files,
            'storages' => $storages
        ]);
    }
}

