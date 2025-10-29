<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Exceptions\InvalidFilenameException;
use Ozdemir\VueFinder\Contracts\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for uploading files
 */
class UploadAction extends BaseAction implements ActionInterface
{
    public function execute(): JsonResponse
    {
        $path = $this->request->get('path');
        $name = $this->request->getPayload()->get('name');

        $file = $this->request->files->get('file');
        
        if (!$file) {
            throw new InvalidFilenameException('No file uploaded.');
        }

        $stream = fopen($file->getRealPath(), 'r+');
        $this->filesystem->writeStream($path . DIRECTORY_SEPARATOR . $name, $stream);
        fclose($stream);

        return new JsonResponse();
    }
}

