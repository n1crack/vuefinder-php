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


class LegacyVueFinder
{
    private MountManager $manager;
    private array $storages;
    private string $storageKey;
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

        // Extract storage key from path instead of expecting it as a parameter
        $path = $this->request->get('path', '');
        $this->storageKey = $this->extractStorageFromPath($path, array_keys($storages));

        $this->storages = array_keys($storages);

        $storages = array_map(static fn($storage) => new Filesystem($storage), $storages);

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
     * Categorize file size into small, medium, or large
     * @param int $size Size in bytes
     * @return string
     */
    private function categorizeFileSize(int $size): string
    {
        // Define size thresholds (in bytes)
        $smallThreshold = 1024 * 1024; // 1MB
        $mediumThreshold = 10 * 1024 * 1024; // 10MB
        
        if ($size <= $smallThreshold) {
            return 'small';
        } elseif ($size <= $mediumThreshold) {
            return 'medium';
        } else {
            return 'large';
        }
    }

    /**
     * Filter files by size category
     * @param array $files
     * @param string $sizeFilter 'all' | 'small' | 'medium' | 'large'
     * @return array
     */
    private function filterFilesBySize(array $files, string $sizeFilter = 'all'): array
    {
        if ($sizeFilter === 'all') {
            return $files;
        }

        return array_values(array_filter($files, function($file) use ($sizeFilter) {

            if ($file['type'] !== 'file' || !isset($file['file_size'])) {
                return false;
            }

            $fileSizeCategory = $this->categorizeFileSize($file['file_size']);
            return $fileSizeCategory === $sizeFilter;
        }));
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
            'download' => 'get',
            'preview' => 'get',
            'search' => 'get',
            'newfolder' => 'post',
            'newfile' => 'post',
            'rename' => 'post',
            'move' => 'post',
            'copy' => 'post',
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

            // Get storage from path for this request
            $path = $this->request->get('path', '');
            $currentStorageKey = $this->getStorageFromPath($path);
            $storage = $this->storageAdapters[$currentStorageKey];
            $readonly_array = ['index', 'download', 'preview', 'search'];

            if ($storage instanceof ReadOnlyFilesystemAdapter && !in_array($query, $readonly_array, true)) {
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
     * Extract storage key from path
     * @param string $path
     * @param array $availableStorages
     * @return string
     */
    private function extractStorageFromPath(string $path, array $availableStorages): string
    {
        // If path contains storage:// format, extract the storage key
        if (preg_match('/^([^:]+):\/\//', $path, $matches)) {
            $storageKey = $matches[1];
            if (in_array($storageKey, $availableStorages)) {
                return $storageKey;
            }
        }
        
        // Default to first available storage if no valid storage found in path
        return $availableStorages[0];
    }

    /**
     * Get storage key from path, updating the current storage key
     * @param string $path
     * @return string
     */
    private function getStorageFromPath(string $path): string
    {
        $this->storageKey = $this->extractStorageFromPath($path, $this->storages);
        return $this->storageKey;
    }

    /**
     * Custom dirname function that properly handles storage:// paths
     * @param string $path
     * @return string
     */
    private function customDirname(string $path): string
    {
        // Handle storage:// format
        if (preg_match('/^([^:]+):\/\/(.*)$/', $path, $matches)) {
            $storage = $matches[1];
            $actualPath = $matches[2];
            
            // If path is empty or just '.', return storage root
            if (empty($actualPath) || $actualPath === '.') {
                return $storage . '://';
            }
            
            $dirname = dirname($actualPath);
            
            // If dirname returns '.' or empty, return storage root
            if ($dirname === '.' || $dirname === '') {
                return $storage . '://';
            }
            
            return $storage . '://' . $dirname;
        }
        
        // Fallback to regular dirname for non-storage paths
        return dirname($path);
    }

    /**
     * @return JsonResponse
     * @throws FilesystemException
     */
    public function index()
    {
        $path = $this->request->get('path', '');
        $currentStorageKey = $this->getStorageFromPath($path);
        $dirname = $path ?: $currentStorageKey.'://';

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

        $read_only = $this->storageAdapters[$currentStorageKey] instanceof ReadOnlyFilesystemAdapter;
        $storages = $this->storages;
        
        return new JsonResponse(compact(['dirname', 'files', 'read_only', 'storages']));
    }

    /**
     * @return JsonResponse
     * @throws FilesystemException
     */
    public function search()
    {
        $size = $this->request->get('size'); // 'all' | 'small' | 'medium' | 'large'
        $path = $this->request->get('path', '');
        $deep = $this->request->get('deep', false);

        $currentStorageKey = $this->getStorageFromPath($path);
        $dirname = $path ?: $currentStorageKey.'://';
        $filter = $this->request->get('filter');

        $listContents = $this->manager
            ->listContents($dirname, $deep)
            ->map(fn(StorageAttributes $attributes) => $attributes->jsonSerialize())
            ->toArray();

        $files = array_values($this->files($listContents, $filter));

        $files = array_map(function($node) {
            $node['basename'] = basename($node['path']);
            $node['extension'] = pathinfo($node['path'], PATHINFO_EXTENSION);
            $node['dir'] = $this->customDirname($node['path']);

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

        // Apply size filtering if size parameter is provided
        if ($size && in_array($size, ['small', 'medium', 'large', 'all'])) {
            $files = $this->filterFilesBySize($files, $size);
        }

        $storages = $this->storages;
        
        return new JsonResponse(compact(['dirname', 'files', 'storages']));
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

    public function copy()
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

            $this->manager->copy($item['path'], $target);
        }

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
