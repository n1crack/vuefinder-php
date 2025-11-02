<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Exceptions\FileExistsException;
use Ozdemir\VueFinder\Contracts\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for copying files and directories
 */
class CopyAction extends BaseAction implements ActionInterface
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

        // Copy all items
        foreach ($sources as $source) {
            $target = $destination . DIRECTORY_SEPARATOR . basename($source);
            $this->filesystem->copy($source, $target);
        }

        $indexAction = new ListAction(
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

