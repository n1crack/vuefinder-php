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
        $destination = $payload->get('destination');
        $sources = $payload->all('sources');
        
        // Check if any target already exists
        foreach ($sources as $source) {
            $target = $destination . DIRECTORY_SEPARATOR . basename($source);
            if ($this->filesystem->exists($target)) {
                throw new FileExistsException('One of the files already exists.');
            }
        }

        // Move all$sources
        foreach ($sources as $source) {
            $target = $destination . DIRECTORY_SEPARATOR . basename($source);
            $this->filesystem->move($source, $target);
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

