<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Exceptions\FileExistsException;
use Ozdemir\VueFinder\Exceptions\InvalidFilenameException;
use Ozdemir\VueFinder\Contracts\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for creating new files
 */
class CreateFileAction extends BaseAction implements ActionInterface
{
    public function execute(): JsonResponse
    {
        $path = $this->request->get('path');
        $name = $this->request->getPayload()->get('name');

        if (!preg_match('/^[^\\/?%*:|"<>]+$/', $name) || empty($name)) {
            throw new InvalidFilenameException('Invalid file name.');
        }

        $newPath = $path . DIRECTORY_SEPARATOR . $name;

        if ($this->filesystem->exists($newPath)) {
            throw new FileExistsException('The file/folder already exists. Try another name.');
        }

        $this->filesystem->write($newPath, '');

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

