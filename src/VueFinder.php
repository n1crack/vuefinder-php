<?php

namespace Ozdemir\Vuefinder;

use Exception;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemException;
use League\Flysystem\MountManager;
use League\Flysystem\StorageAttributes;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use League\Flysystem\Filesystem;

class VueFinder
{
    private $storage;
    private $config;
    private array $storages;
    private $adapter;
    private $rootPath;
    private MountManager $manager;
    private $realpaths;

    /**
     * VueFinder constructor.
     * @param  array  $storages
     */
    public function __construct(array $storages)
    {
        $this->request = Request::createFromGlobals();
        $this->adapterKey = $this->request->get('adapter', array_keys($storages)[0]);
        $this->storages = array_keys($storages);

        $storage = [];
        $this->realpaths = [];
        foreach ($storages as $key => [$adapter, $args]) {
            $storage[$key] = new Filesystem(new $adapter(...$args));
            $this->realpaths[$key] = $args[0];
        }

        $this->manager = new MountManager($storage);

        [$adapter, $args] = $storages[$this->adapterKey];

        $this->adapter = new $adapter(...$args);

        $this->rootPath = realpath($args[0]);

        $this->storage = new Filesystem($this->adapter);
    }

    /**
     * @param $files
     * @return array
     */
    public function directories($files)
    {
        return array_filter($files, static fn($item) => $item['type'] == 'dir');
    }

    /**
     * @param $files
     * @return array
     */
    public function files($files)
    {
        return array_filter($files, static fn($item) => $item['type'] == 'file');
    }

    /**
     * @param $config
     */
    public function init($config)
    {
        $this->config = $config;
        $query = $this->request->get('q');
        $route_array = ['index', 'newfolder', 'newfile', 'read', 'download', 'rename', 'move', 'delete', 'upload', 'archive', 'preview'];

        try {
            if (!in_array($query, $route_array, true)) {
                throw new Exception('The query does not have a valid method.');
            }
            $response = $this->$query();
            $response->headers->set('Access-Control-Allow-Origin', "*");
            $response->headers->set('Access-Control-Allow-Headers', "*");
            $response->send();

        } catch (Exception $e) {
            $response = new JsonResponse(['status' => false, 'message' => $e->getMessage()], 400);
            $response->headers->set('Access-Control-Allow-Origin', "*");
            $response->headers->set('Access-Control-Allow-Headers', "*");
            $response->send();
        }
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

        $files = array_map(function($node)  {
            $node['basename'] = basename($node['path']);
            $node['extension'] = pathinfo($node['path'], PATHINFO_EXTENSION);
            $node['storage'] = $this->adapterKey;

            if ($node['type'] != 'dir') {
                try {
                    $node['mime_type'] = $this->storage->mimeType($node['path']);
                    // it is ok!
                } catch (Exception $exception) {
                    // it failed!
                }
            }
            if ($this->config['publicPaths'] && $node['type'] != 'dir') {
                foreach ($this->config['publicPaths'] as $publicPath => $domain) {
                    $publicPath = str_replace('/', '\/', $publicPath);
                    if (preg_match('/^'.$publicPath.'/i', $node['path'])) {
                        $node['url'] = preg_replace('/^'.$publicPath.'/i', $domain, $node['path']);
                    }
                }
            }

            return $node;
        }, $files);

        $storages = $this->storages;
        $adapter = $this->adapterKey;

        return new JsonResponse(compact(['adapter', 'storages', 'dirname', 'files']));
    }

    /**
     * @return JsonResponse
     */
    public function newfolder()
    {
        $path = $this->request->get('path');
        $name = $this->request->get('name');

        if (!strpbrk($name, "\\/?%*:|\"<>") === false) {
            throw new Exception('Invalid folder name.');
        }
        $this->storage->createDirectory("$path/$name");

        return $this->index();
    }

    /**
     * @return JsonResponse
     */
    public function newfile()
    {
        $path = $this->request->get('path');
        $name = $this->request->get('name');

        if (!strpbrk($name, "\\/?%*:|\"<>") === false) {
            throw new Exception('Invalid file name.');
        }

        $this->storage->write($path. DIRECTORY_SEPARATOR .$name, '');

        return $this->index();
    }

    /**
     * @return JsonResponse
     * @throws FileExistsException
     */
    public function upload()
    {
        $path = $this->request->get('path');
        $file = $this->request->files->get('file');

        $stream = fopen($file->getRealPath(), 'r+');
        $this->storage->writeStream(
            $path.DIRECTORY_SEPARATOR.$file->getClientOriginalName(),
            $stream
        );

        is_resource($stream) && fclose($stream);

        return new JsonResponse(['status' => true]);
    }

    /**
     * @return StreamedResponse
     * @throws FileNotFoundException
     */
    public function read()
    {
        $path = $this->request->get('path');


        return $this->streamFile($path);
    }

    public function preview()
    {
        $path = $this->request->get('path');

        return $this->streamFile($path);
    }


    /**
     * @return StreamedResponse
     * @throws FileNotFoundException
     */
    public function download()
    {
        $path = $this->request->get('path');
        $response = $this->streamFile($path);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($path)
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @return JsonResponse
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    public function rename()
    {
        $from = $this->request->get('item');
        $to = dirname($from).DIRECTORY_SEPARATOR.$this->request->get('name');

        $this->storage->move($from, $to);

        return $this->index();
    }

    public function move()
    {
        $to = $this->request->get('item');

        $items = json_decode($this->request->get('items'));

        foreach ($items as $item) {
            $target = $to.DIRECTORY_SEPARATOR.basename($item->path);

            $this->storage->move($item->path, $target);
        }

        return $this->index();
    }

    /**
     * @return JsonResponse
     * @throws FileNotFoundException
     */
    public function delete()
    {
        $items = json_decode($this->request->get('items'));

        foreach ($items as $item) {
            if ($item->type == 'dir') {
                $this->storage->deleteDirectory($item->path);
            } else {
                $this->storage->delete($item->path);
            }
        }

        return $this->index();
    }

        /**
     * @return JsonResponse
     * @throws FileNotFoundException
     */
    public function archive()
    {
        $items = json_decode($this->request->get('items'));
        $zipFile = $this->rootPath.DIRECTORY_SEPARATOR.$this->request->get('name');

        $zipStorage = new Filesystem(
            new ZipArchiveAdapter(
                new FilesystemZipArchiveProvider(
                    $zipFile,
                ),
            ),
        );
        foreach ($items as $item) {
            if ($item->type == 'dir') {
                $dirFiles = $this->storage->listContents($item->path, true)
                    ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
                    ->toArray();
                foreach ($dirFiles as $dirFile) {
                    $file = $this->storage->readStream($dirFile->path());

                    $zipStorage->writeStream($dirFile->path(), $file);
                }
            } else {
                $file = $this->storage->readStream($item->path);

                $zipStorage->writeStream($item->path, $file);
            }
        }

        return $this->index();
    }

    /**
     * @param $path
     * @return StreamedResponse
     * @throws FileNotFoundException|FilesystemException
     */
    public function streamFile($path)
    {
        $stream = $this->storage->readStream($path);

        $response = new StreamedResponse();

        $mimeType = $this->storage->mimeType($path);
        $size = $this->storage->fileSize($path);

        $response->headers->set('Access-Control-Allow-Origin', "*");
        $response->headers->set('Access-Control-Allow-Headers', "*");
        $response->headers->set('Content-Length', $size);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Accept-Ranges', 'bytes');

        if ( isset($_SERVER['HTTP_RANGE']) ) {
            header('HTTP/1.1 206 Partial Content');
        }

        $response->setCallback(function() use ($stream) {
            ob_end_clean();
            fpassthru($stream);
        });

        return $response;
    }
}
