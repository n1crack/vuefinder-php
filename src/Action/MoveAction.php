<?php

namespace Ozdemir\VueFinder\Action;

use Ozdemir\VueFinder\Exception\FileExistsException;
use Ozdemir\VueFinder\Interface\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for moving files and directories
 */
class MoveAction extends BaseAction implements ActionInterface
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

        // Move all items
        foreach ($items as $item) {
            $target = $to . DIRECTORY_SEPARATOR . basename($item['path']);
            $this->filesystem->move($item['path'], $target);
        }

        $indexAction = new IndexAction(
            $this->request,
            $this->filesystem,
            $this->pathParser,
            $this->storageResolver,
            $this->urlResolver,
            $this->config
        );
        
        return $indexAction->execute();
    }
}

