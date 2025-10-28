<?php

namespace Ozdemir\VueFinder\Action;

use Ozdemir\VueFinder\Interface\ActionInterface;

/**
 * Action for saving file content
 */
class SaveAction extends BaseAction implements ActionInterface
{
    public function execute(): \Symfony\Component\HttpFoundation\Response
    {
        $path = $this->request->get('path');
        $content = $this->request->getPayload()->get('content');

        $this->filesystem->write($path, $content);

        $previewAction = new PreviewAction(
            $this->request,
            $this->filesystem,
            $this->pathParser,
            $this->storageResolver,
            $this->urlResolver,
            $this->config
        );
        
        return $previewAction->execute();
    }
}

