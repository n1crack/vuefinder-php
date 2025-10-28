<?php

namespace Ozdemir\VueFinder\Action;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Ozdemir\VueFinder\Action\IndexAction;
use Ozdemir\VueFinder\Interface\ActionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Action for unarchiving zip files
 */
class UnarchiveAction extends BaseAction implements ActionInterface
{
    public function execute(): JsonResponse
    {
        $zipItem = $this->request->getPayload()->get('item');
        $zipStream = $this->filesystem->readStream($zipItem);
        $zipFile = tempnam(sys_get_temp_dir(), $zipItem);
        file_put_contents($zipFile, $zipStream);

        $zipStorage = new Filesystem(
            new ZipArchiveAdapter(
                new FilesystemZipArchiveProvider($zipFile)
            )
        );

        $dirContents = iterator_to_array($zipStorage->listContents('', true));
        $dirFiles = array_filter($dirContents, fn($file) => $file->isFile());

        $path = $this->request->get('path') . DIRECTORY_SEPARATOR . pathinfo($zipItem, PATHINFO_FILENAME) . DIRECTORY_SEPARATOR;

        foreach ($dirFiles as $dirFile) {
            if ($dirFile instanceof \League\Flysystem\StorageAttributes && $dirFile->isFile()) {
                $file = $zipStorage->readStream($dirFile->path());
                $this->filesystem->writeStream($path . $dirFile->path(), $file);
            }
        }

        unlink($zipFile);

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

