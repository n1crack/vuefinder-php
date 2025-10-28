<?php

namespace Ozdemir\VueFinder\Action;

use Ozdemir\VueFinder\Interface\ActionInterface;
use Ozdemir\VueFinder\Interface\FilesystemServiceInterface;
use Ozdemir\VueFinder\Interface\PathParserInterface;
use Ozdemir\VueFinder\Interface\StorageResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Factory for creating action instances
 */
class ActionFactory
{
    private Request $request;
    private FilesystemServiceInterface $filesystem;
    private PathParserInterface $pathParser;
    private StorageResolverInterface $storageResolver;
    private array $config;

    public function __construct(
        Request $request,
        FilesystemServiceInterface $filesystem,
        PathParserInterface $pathParser,
        StorageResolverInterface $storageResolver,
        array $config
    ) {
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->pathParser = $pathParser;
        $this->storageResolver = $storageResolver;
        $this->config = $config;
    }

    /**
     * Create an action instance
     * 
     * @param string $actionName
     * @return ActionInterface
     */
    public function create(string $actionName): ActionInterface
    {
        $actionClass = $this->getActionClass($actionName);
        
        return new $actionClass(
            $this->request,
            $this->filesystem,
            $this->pathParser,
            $this->storageResolver,
            $this->config
        );
    }

    /**
     * Get the action class name for a given action
     * 
     * @param string $actionName
     * @return string
     */
    protected function getActionClass(string $actionName): string
    {
        $actions = [
            'index' => IndexAction::class,
            'download' => DownloadAction::class,
            'preview' => PreviewAction::class,
            'search' => SearchAction::class,
            'create-folder' => CreateFolderAction::class,
            'create-file' => CreateFileAction::class,
            'rename' => RenameAction::class,
            'move' => MoveAction::class,
            'copy' => CopyAction::class,
            'delete' => DeleteAction::class,
            'upload' => UploadAction::class,
            'archive' => ArchiveAction::class,
            'unarchive' => UnarchiveAction::class,
            'save' => SaveAction::class,
        ];

        if (!isset($actions[$actionName])) {
            throw new \InvalidArgumentException("Unknown action: {$actionName}");
        }

        return $actions[$actionName];
    }
}

