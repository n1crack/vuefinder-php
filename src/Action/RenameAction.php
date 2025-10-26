<?php

namespace Ozdemir\VueFinder\Action;

use Ozdemir\VueFinder\Exception\FileExistsException;
use Ozdemir\VueFinder\Exception\InvalidFilenameException;
use Ozdemir\VueFinder\Interface\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for renaming files and directories
 */
class RenameAction extends BaseAction implements ActionInterface
{
    public function execute(): JsonResponse
    {
        $payload = $this->request->getPayload();
        $name = $payload->get('name');
        $from = $payload->get('item');
        $path = $this->request->get('path');
        $to = $path . DIRECTORY_SEPARATOR . $name;

        if (!preg_match('/^[^\\/?%*:|"<>]+$/', $name) || empty($name)) {
            throw new InvalidFilenameException('Invalid file name.');
        }

        if ($this->filesystem->exists($to)) {
            throw new FileExistsException('The file/folder already exists.');
        }

        $this->filesystem->move($from, $to);

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

