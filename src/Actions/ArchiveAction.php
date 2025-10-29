<?php

namespace Ozdemir\VueFinder\Actions;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Ozdemir\VueFinder\Exceptions\FileExistsException;
use Ozdemir\VueFinder\Exceptions\InvalidFilenameException;
use Ozdemir\VueFinder\Actions\ListAction;
use Ozdemir\VueFinder\Contracts\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for archiving files and directories
 */
class ArchiveAction extends BaseAction implements ActionInterface
{
    public function execute(): JsonResponse
    {
        $payload = $this->request->getPayload();
        $name = pathinfo($payload->get('name'), PATHINFO_FILENAME);
        
        if (!preg_match('/^[^\\/?%*:|"<>]+$/', $name) || empty($name)) {
            throw new InvalidFilenameException('Invalid file name.');
        }

        $items = $payload->all('items');
        $name .= '.zip';
        $path = $this->request->get('path') . DIRECTORY_SEPARATOR . $name;
        $zipFile = tempnam(sys_get_temp_dir(), $name);

        if ($this->filesystem->exists($path)) {
            throw new FileExistsException('The archive already exists. Try another name.');
        }

        $zipStorage = new Filesystem(
            new ZipArchiveAdapter(
                new FilesystemZipArchiveProvider($zipFile)
            )
        );

        foreach ($items as $item) {
            if ($item['type'] == 'dir') {
                $dirContents = $this->filesystem->listContents($item['path'], true);
                $dirFiles = array_filter($dirContents, fn($file) => isset($file['type']) && $file['type'] == 'file');
                
                foreach ($dirFiles as $dirFile) {
                    $file = $this->filesystem->readStream($dirFile['path']);
                    $relativePath = str_replace($this->request->get('path'), '', $dirFile['path']);
                    $zipStorage->writeStream($relativePath, $file);
                }
            } else {
                $file = $this->filesystem->readStream($item['path']);
                $relativePath = str_replace($this->request->get('path'), '', $item['path']);
                $zipStorage->writeStream($relativePath, $file);
            }
        }

        if ($zipStream = fopen($zipFile, 'r')) {
            $this->filesystem->writeStream($path, $zipStream);
            fclose($zipStream);
        }
        unlink($zipFile);

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

