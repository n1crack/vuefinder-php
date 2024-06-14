<?php

namespace Ozdemir\VueFinder;

use Exception;
use JsonException;
use League\Flysystem\Filesystem;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemException;
use League\Flysystem\MountManager;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


class VueFinder
{
    private MountManager $manager;
    private array $storages;
    private string $adapterKey;
    private Request $request;
    private $config;
    private array $storageAdapters;

    /**
     * VueFinder constructor.
     * @param  array  $storages
     */
    public function __construct(array $storages)
    {

        $this->storageAdapters = $storages;

        $this->request = Request::createFromGlobals();

        $this->adapterKey = $this->request->get('adapter');

        if (!$this->adapterKey || !in_array($this->adapterKey, array_keys($storages)) ) {
            $this->adapterKey = array_keys($storages)[0];
        }

        $this->storages = array_keys($storages);

        $storages = array_map(static fn($adapter) => new Filesystem($adapter), $storages);

        $this->manager = new MountManager($storages);
    }

    /**
     * @param $files
     * @return array
     */
    public function directories($files): array
    {
        return array_filter($files, static fn($item) => $item['type'] == 'dir');
    }

    /**
     * @param $files
     * @return array
     */
    public function files($files, $search = false): array
    {
        return array_filter(
            $files,
            static fn($item) => $item['type'] == 'file' && (!$search || fnmatch("*$search*", $item['path'], FNM_CASEFOLD))
        );
    }

    /**
     * @param $config
     */
    public function init($config): void
    {
        $this->config = $config;
        $query = $this->request->get('q');

        $route_array = [
            'index' => 'get',
            'subfolders' => 'get',
            'download' => 'get',
            'preview' => 'get',
            'search' => 'get',
            'newfolder' => 'post',
            'newfile' => 'post',
            'rename' => 'post',
            'move' => 'post',
            'delete' => 'post',
            'upload' => 'post',
            'archive' => 'post',
            'unarchive' => 'post',
            'save' => 'post',
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', "*");
            $response->headers->set('Access-Control-Allow-Headers', "*");
            $response->send();
            return;
        }

        try {
            if (!array_key_exists($query, $route_array)
                || $route_array[$query] !== strtolower($this->request->getMethod())) {
                throw new Exception('The query does not have a valid method.');
            }

            $adapter = $this->storageAdapters[$this->adapterKey];
            $readonly_array = ['index', 'download', 'preview', 'search', 'subfolders'];

            if ($adapter instanceof ReadOnlyFilesystemAdapter && !in_array($query, $readonly_array, true)) {
                throw new Exception('This is a readonly storage.');
            }

            $response = $this->$query();
        } catch (Exception $e) {
            $response = new JsonResponse(['status' => false, 'message' => $e->getMessage()], 400);
        }

        $response->headers->set('Access-Control-Allow-Origin', "*");
        $response->headers->set('Access-Control-Allow-Headers', "*");

        $response->send();
    }

    public function config($key)
    {
        return $this->config[$key] ?? null;
    }

    /**
     * @return JsonResponse
     * @throws FilesystemException
     */
    public function index()
    {
        $dirname = $this->request->get('path', $this->adapterKey.'://');

        $listContents = $this->manager
            ->listContents($dirname)
            ->map(fn(StorageAttributes $attributes) => $attributes->jsonSerialize())
            ->toArray();

        $files = array_merge(
            $this->directories($listContents),
            $this->files($listContents)
        );

        $files = array_map(function($node) {
            $node['basename'] = basename($node['path']);
            $node['extension'] = pathinfo($node['path'], PATHINFO_EXTENSION);
            $node['storage'] = $this->adapterKey;

            if ($node['type'] != 'dir' && $node['extension']) {
                try {
                    $node['mime_type'] = $this->manager->mimeType($node['path']);
                } catch (Exception $exception) {
                    //
                }
            }
            $this->setPublicLinks($node);

            return $node;
        }, $files);

        $storages = $this->storages;
        $adapter = $this->adapterKey;

        return new JsonResponse(compact(['adapter', 'storages', 'dirname', 'files']));
    }

    public function subfolders()
    {
        $dirname = $this->request->get('path', $this->adapterKey . '://');

        $folders = $this->manager
            ->listContents($dirname)
            ->filter(fn(StorageAttributes $attributes) => $attributes->isDir())
            ->map(fn(StorageAttributes $attributes) => [
                'adapter' => $this->adapterKey,
                'path' => $attributes->path(),
                'basename' => basename($attributes->path()),
            ])
            ->toArray();;

        return new JsonResponse(compact(['folders']));
    }

    /**
     * @return JsonResponse
     * @throws FilesystemException
     */
    public function search()
    {
        $dirname = $this->request->get('path', $this->adapterKey.'://');
        $filter = $this->request->get('filter');

        $listContents = $this->manager
            ->listContents($dirname, true)
            ->map(fn(StorageAttributes $attributes) => $attributes->jsonSerialize())
            ->toArray();

        $files = array_values($this->files($listContents, $filter));

        $files = array_map(function($node) {
            $node['basename'] = basename($node['path']);
            $node['extension'] = pathinfo($node['path'], PATHINFO_EXTENSION);
            $node['storage'] = $this->adapterKey;
            $node['dir'] = dirname($node['path']);

            if ($node['type'] != 'dir') {
                try {
                    $node['mime_type'] = $this->manager->mimeType($node['path']);
                } catch (Exception $exception) {
                    //
                }
            }
            $this->setPublicLinks($node);

            return $node;
        }, $files);

        $storages = $this->storages;
        $adapter = $this->adapterKey;

        return new JsonResponse(compact(['adapter', 'storages', 'dirname', 'files']));
    }

    /**
     * @return JsonResponse
     * @throws FilesystemException
     */
    public function newfolder()
    {
        $path = $this->request->get('path');
        $name = $this->request->getPayload()->get('name');

        if (!$name || !strpbrk($name, "\\/?%*:|\"<>") === false) {
            throw new Exception('Invalid folder name.');
        }

        $newPath = $path.DIRECTORY_SEPARATOR.$name;

        if ($this->manager->fileExists($newPath) || $this->manager->directoryExists($newPath)) {
            throw new Exception('The file/folder is already exists. Try another name.');
        }

        $this->manager->createDirectory($newPath);

        return $this->index();
    }

    /**
     * @return JsonResponse
     */
    public function newfile()
    {
        $path = $this->request->get('path');
        $name = $this->request->getPayload()->get('name');

        if (!$name || !strpbrk($name, "\\/?%*:|\"<>") === false) {
            throw new Exception('Invalid file name.');
        }
        $newPath = $path.DIRECTORY_SEPARATOR.$name;

        if ($this->manager->fileExists($newPath) || $this->manager->directoryExists($newPath)) {
            throw new Exception('The file/folder is already exists. Try another name.');
        }

        $this->manager->write($newPath, '');

        return $this->index();
    }

    /**
     * @return JsonResponse
     *
     * @throws FilesystemException
     */
    public function upload()
    {
        $path = $this->request->get('path');
        $name = $this->request->getPayload()->get('name');

        $file = $this->request->files->get('file');
        $stream = fopen($file->getRealPath(), 'r+');

        $this->manager->writeStream(
            $path.DIRECTORY_SEPARATOR.$name,
            $stream
        );
        fclose($stream);

        return new JsonResponse(['ok']);
    }

    public function preview()
    {
        $path = $this->request->get('path');

        return $this->streamFile($path);
    }


    public function save()
    {
        $path = $this->request->get('path');
        $content = $this->request->getPayload()->get('content');

        $this->manager->write($path, $content);

        return $this->preview();
    }

    /**
     * @return StreamedResponse
     * @throws FileNotFoundException
     */
    public function download()
    {
        $path = $this->request->get('path');
        $response = $this->streamFile($path);

        $filenameFallback = preg_replace(
            '#^.*\.#',
            md5($path) . '.', $path
        );

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($path),
            $filenameFallback
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @return JsonResponse
     * @throws FilesystemException
     */
    public function rename()
    {
        $payload = $this->request->getPayload();
        $name = $payload->get('name');
        $from = $payload->get('item');
        $path = $this->request->get('path');
        $to = $path.DIRECTORY_SEPARATOR.$name;

        if ($this->manager->fileExists($to) || $this->manager->directoryExists($to)) {
            throw new Exception('The file/folder is already exists.');
        }

        $this->manager->move($from, $to);

        return $this->index();
    }

    public function move()
    {
        $payload = $this->request->getPayload();
        $to = $payload->get('item');
        $items = $payload->all('items');

        foreach ($items as $item) {
            $target = $to.DIRECTORY_SEPARATOR.basename($item['path']);
            if ($this->manager->fileExists($target) || $this->manager->directoryExists($target)) {
                throw new Exception('One of the files is already exists.');
            }
        }

        foreach ($items as $item) {
            $target = $to.DIRECTORY_SEPARATOR.basename($item['path']);

            $this->manager->move($item['path'], $target);
        }

        return $this->index();
    }

    /**
     * @return JsonResponse
     * @throws FileNotFoundException
     */
    public function delete()
    {
        $items = $this->request->getPayload()->all("items");

        foreach ($items as $item) {
            if ($item['type'] == 'dir') {
                $this->manager->deleteDirectory($item['path']);
            } else {
                $this->manager->delete($item['path']);
            }
        }

        return $this->index();
    }

    /**
     * @return JsonResponse
     * @throws FileNotFoundException
     * @throws JsonException|FilesystemException
     */
    public function archive()
    {
        $payload = $this->request->getPayload();
        $name = pathinfo($payload->get('name'), PATHINFO_FILENAME);
        if (!$name || !strpbrk($name, "\\/?%*:|\"<>") === false) {
            throw new Exception('Invalid file name.');
        }

        $items = $payload->all('items');
        $name .= '.zip';
        $path = $this->request->get('path').DIRECTORY_SEPARATOR.$name;
        $zipFile = tempnam(sys_get_temp_dir(), $name);

        if ($this->manager->fileExists($path)) {
            throw new Exception('The archive is exists. Try another name.');
        }

        $zipStorage = new Filesystem(
            new ZipArchiveAdapter(
                new FilesystemZipArchiveProvider(
                    $zipFile,
                ),
            ),
        );

        foreach ($items as $item) {
            if ($item['type'] == 'dir') {
                $dirFiles = $this->manager->listContents($item['path'], true)
                    ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
                    ->toArray();
                foreach ($dirFiles as $dirFile) {
                    $file = $this->manager->readStream($dirFile->path());
                    $zipStorage->writeStream(str_replace($this->request->get('path'), '', $dirFile->path()), $file);
                }
            } else {
                $file = $this->manager->readStream($item['path']);
                $zipStorage->writeStream(str_replace($this->request->get('path'), '', $item['path']), $file);
            }
        }

        if ($zipStream = fopen($zipFile, 'r')) {
            $this->manager->writeStream($path, $zipStream);
            fclose($zipStream);
        }
        unlink($zipFile);

        return $this->index();
    }

    /**
     * @return JsonResponse
     * @throws FileNotFoundException
     * @throws FilesystemException
     */
    public function unarchive()
    {
        $zipItem = $this->request->getPayload()->get('item');

        $zipStream = $this->manager->readStream($zipItem);

        $zipFile = tempnam(sys_get_temp_dir(), $zipItem);

        file_put_contents($zipFile, $zipStream);

        $zipStorage = new Filesystem(
            new ZipArchiveAdapter(
                new FilesystemZipArchiveProvider(
                    $zipFile,
                ),
            ),
        );

        $dirFiles = $zipStorage->listContents('', true)
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
            ->toArray();

        $path = $this->request->get('path').DIRECTORY_SEPARATOR.pathinfo($zipItem, PATHINFO_FILENAME).DIRECTORY_SEPARATOR;


        foreach ($dirFiles as $dirFile) {
            $file = $zipStorage->readStream($dirFile->path());
            $this->manager->writeStream($path.$dirFile->path(), $file);
        }

        unlink($zipFile);

        return $this->index();
    }

    /**
     * @param $path
     * @return StreamedResponse
     * @throws FileNotFoundException|FilesystemException
     */
    public function streamFile($path)
    {
        $stream = $this->manager->readStream($path);

        $response = new StreamedResponse();

        try {
            $mimeType = $this->manager->mimeType($path);
        } catch (Exception $exception) {
            $mimeType = 'application/octet-stream';
        }

        $size = $this->manager->fileSize($path);

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

    private function setPublicLinks(mixed &$node)
    {
        $publicLinks = $this->config('publicLinks');

        if ($publicLinks && $node['type'] != 'dir') {
            foreach ($publicLinks as $publicLink => $domain) {
                $publicLink = str_replace('/', '\/', $publicLink);

                if (preg_match('/^'.$publicLink.'/i', $node['path'])) {
                    $node['url'] = preg_replace('/^'.$publicLink.'/i', $domain, $node['path']);
                }
            }
        }
    }
}
