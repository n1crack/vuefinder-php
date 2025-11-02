<?php

namespace Ozdemir\VueFinder\Actions;

use Ozdemir\VueFinder\Contracts\ActionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Action for previewing files
 */
class PreviewAction extends BaseAction implements ActionInterface
{
    public function execute(): Response
    {
        $path = $this->request->get('path');
        return $this->streamFile($path);
    }

    /**
     * Stream a file
     * 
     * @param string $path
     * @return StreamedResponse
     */
    protected function streamFile(string $path): StreamedResponse
    {
        $stream = $this->filesystem->readStream($path);
        $response = new StreamedResponse();

        try {
            $mimeType = $this->filesystem->mimeType($path);
        } catch (\Exception $exception) {
            $mimeType = 'application/octet-stream';
        }

        $size = $this->filesystem->fileSize($path);

        $response->headers->set('Access-Control-Allow-Origin', "*");
        $response->headers->set('Access-Control-Allow-Headers', "*");
        $response->headers->set('Content-Length', $size);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Accept-Ranges', 'bytes');

        if (isset($_SERVER['HTTP_RANGE'])) {
            header('HTTP/1.1 206 Partial Content');
        }

        $response->setCallback(function() use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }
}

