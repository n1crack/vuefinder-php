<?php

namespace Ozdemir\Vuefinder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use League\Flysystem\Filesystem;

class VueFinder
{
    private $storage;
    private $config;

    /**
     * VueFinder constructor.
     * @param Filesystem $storage
     */
    public function __construct(Filesystem $storage)
    {
        $this->storage = $storage;
        $this->request = Request::createFromGlobals();
    }

    /**
     * @param $files
     * @return array
     */
    public function directories($files)
    {
        return array_filter($files, function ($item) {
            return $item['type'] == 'dir';
        });
    }

    /**
     * @param $files
     * @return array
     */
    public function files($files)
    {
        return array_filter($files, function ($item) {
            return $item['type'] == 'file';
        });
    }

    /**
     * @param $config
     */
    public function init($config)
    {
        $this->config = $config;
        $query = $this->request->get('q');
        $route_array = ['index', 'newfolder', 'read', 'download', 'rename', 'delete', 'upload'];

        try {
            if (!\in_array($query, $route_array, true)) {
                throw new \Exception('The query does not have a valid method.');
            }
            $response = $this->$query();
            $response->send();
        } catch (\Exception $e) {
            $response = new JsonResponse(['status' => false, 'message' => $e->getMessage()], 400);
            $response->send();
        }
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $root = '.';
        $dirname = $this->request->get('path') ?? $root;
        $parent = \dirname($dirname);
        $types = $this->typeMap();

        $listcontent = $this->storage->listContents($dirname);

        $files = array_merge(
            $this->directories($listcontent),
            $this->files($listcontent)
        );

        $files = array_map(function ($node) use ($types) {
            if ($node['type'] == 'file' && isset($node['extension'])) {
                $node['type'] = $types[mb_strtolower($node['extension'])] ?? 'file';
            }

            if ($node['type'] == 'dir') {
                $node['type'] = 'folder';
            }

            if ($this->config['publicPaths'] && $node['type'] != 'folder') {
                foreach ($this->config['publicPaths'] as $path => $domain) {
                    $path = str_replace('/', '\/', $path);
                    if (preg_match('/^'.$path.'/i', $node['path'])) {
                        $node['fileUrl'] = preg_replace('/^'.$path.'/i', $domain, $node['path']);
                    }
                }
            }

            return $node;
        }, $files);

        return new JsonResponse(compact('root', 'parent', 'dirname', 'files'));
    }

    /**
     * @return JsonResponse
     */
    public function newfolder()
    {
        $path = $this->request->get('path');
        $name = $this->request->get('name');

        if (!strpbrk($name, "\\/?%*:|\"<>") === false) {
            throw new \Exception('Invalid folder name.');
        }

        return new JsonResponse(['status' => $this->storage->createDir("{$path}/{$name}")]);
    }

    /**
     * @return JsonResponse
     * @throws \League\Flysystem\FileExistsException
     */
    public function upload()
    {
        $path = $this->request->get('path');
        $file = $this->request->files->get('file');

        $stream = fopen($file->getRealPath(), 'r+');
        $this->storage->writeStream(
            $path.'/'.$file->getClientOriginalName(),
            $stream
        );

        is_resource($stream) && fclose($stream);

        return new JsonResponse(['status' => true]);
    }

    /**
     * @return StreamedResponse
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function read()
    {
        $path = $this->request->get('path');
        return $this->streamFile($path);
    }

    /**
     * @return StreamedResponse
     * @throws \League\Flysystem\FileNotFoundException
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
     * @throws \League\Flysystem\FileExistsException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function rename()
    {
        $from = $this->request->get('from');
        $to = $this->request->get('to');

        $status = $this->storage->rename($from, $to);

        return  new JsonResponse(['status' => $status]);
    }

    /**
     * @return JsonResponse
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function delete()
    {
        $items = json_decode($this->request->get('items'));

        foreach ($items as $item) {
            if ($item->type == 'folder') {
                $this->storage->deleteDir($item->path);
            } else {
                $this->storage->delete($item->path);
            }
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     * @return array
     */
    private function typeMap()
    {
        $types = [
            'file-image' => ['ai', 'bmp', 'gif', 'ico', 'jpeg', 'jpg', 'png', 'ps', 'psd', 'svg', 'tif', 'tiff'],
            'file-excel' => ['ods', 'xlr', 'xls', 'xlsx'],
            'file-alt' => ['txt'],
            'file-pdf' => ['pdf'],
            'file-code' => ['c', 'class', 'cpp', 'cs', 'h', 'java', 'sh', 'swift', 'vb', 'js', 'css', 'htm', 'html', 'php'],
            'file-archive' => ['zip', 'zipx', 'tar', '7z', 'tar.bz2', 'tar.gz', 'z', 'pkg', 'deb', 'rpm'],
            'file-word' => ['doc', 'docx', 'odt', 'rtf', 'tex', 'wks', 'wps', 'wpd'],
            'file-powerpoint' => ['key', 'odp', 'pps', 'ppt', 'pptx'],
            'file-audio' => ['aif', 'cda', 'mid', 'midi', 'mp3', 'mpa', 'ogg', 'wav', 'wma', 'wpl'],
            'file-video' => ['3g2', '3gp', 'avi', 'flv', 'h264', 'mkv', 'm4v', 'mov', 'mp4', 'mpg', 'mpeg', 'swf', 'wmv']
        ];

        $types = array_map('array_fill_keys', $types, array_keys($types));

        return array_merge(...$types);
    }

    /**
     * @param $path
     * @return StreamedResponse
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function streamFile($path)
    {
        $stream = $this->storage->readStream($path);

        $response = new StreamedResponse();

        $mimeType = $this->storage->getMimetype($path);
        $size = $this->storage->getSize($path);

        $response->headers->set('Content-Length', $size);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');

        $response->setCallback(function () use ($stream) {
            ob_end_clean();
            fpassthru($stream);
        });

        return $response;
    }
}
