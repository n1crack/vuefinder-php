<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Actions\ListAction;
use Ozdemir\VueFinder\Contracts\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for deleting files and directories
 */
class DeleteAction extends BaseAction implements ActionInterface
{
    public function execute(): JsonResponse
    {
        $items = $this->request->getPayload()->all('items');

        foreach ($items as $item) {
            if ($item['type'] == 'dir') {
                $this->filesystem->deleteDirectory($item['path']);
            } else {
                $this->filesystem->delete($item['path']);
            }
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

