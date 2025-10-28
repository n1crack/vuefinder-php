<?php

namespace Ozdemir\VueFinder\Action;

use Ozdemir\VueFinder\VueFinderCore;
use Ozdemir\VueFinder\Interface\ActionInterface;
use Ozdemir\VueFinder\Interface\UrlResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Factory for creating VueFinder actions
 * Framework agnostic - just create actions
 */
class VueFinderActionFactory
{
    private VueFinderCore $core;
    private ?Request $request;

    public function __construct(VueFinderCore $core, ?Request $request = null)
    {
        $this->core = $core;
        $this->request = $request;
    }

    /**
     * Create an action by name
     * 
     * @param string $actionName
     * @return ActionInterface
     */
    public function create(string $actionName): \Ozdemir\VueFinder\Interface\ActionInterface
    {
        $actionClass = $this->getActionClass($actionName);
        
        // Ensure we have a request object
        $request = $this->request ?? Request::createFromGlobals();
        
        return new $actionClass(
            $request,
            $this->core->getFilesystem(),
            $this->core->getPathParser(),
            $this->core->getStorageResolver(),
            $this->core->getUrlResolver(),
            $this->core->getConfig()
        );
    }

    /**
     * Set the request object
     * 
     * @param Request $request
     * @return self
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get the action class name
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

