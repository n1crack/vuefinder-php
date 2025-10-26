<?php

namespace Ozdemir\VueFinder\Action;

use Ozdemir\VueFinder\Exception\FileExistsException;
use Ozdemir\VueFinder\Interface\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for copying files and directories
 */
class CopyAction extends BaseAction implements ActionInterface
{
    public function execute(): JsonResponse
    {
        $payload = $this->request->getPayload();
        $to = $payload->get('item');
        $items = $payload->all('items');

        // Check if any target already exists
        foreach ($items as $item) {
            $target = $to . DIRECTORY_SEPARATOR . basename($item['path']);
            if ($this->filesystem->exists($target)) {
                throw new FileExistsException('One of the files already exists.');
            }
        }

        // Copy all items
        foreach ($items as $item) {
            $target = $to . DIRECTORY_SEPARATOR . basename($item['path']);
            $this->filesystem->copy($item['path'], $target);
        }

        $indexAction = new IndexAction(
            $this->request,
            $this->filesystem,
            $this->pathParser,
            $this->storageResolver,
            $this->config
        );
        
        return $indexAction->execute();
    }
}

