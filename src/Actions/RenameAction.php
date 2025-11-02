<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Exceptions\FileExistsException;
use Ozdemir\VueFinder\Exceptions\InvalidFilenameException;
use Ozdemir\VueFinder\Contracts\ActionInterface;
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

