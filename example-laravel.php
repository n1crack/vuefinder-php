<?php

/**
 * VueFinder Laravel Integration Example
 * 
 * This example shows a complete, production-ready Laravel integration
 */

require 'vendor/autoload.php';

use Ozdemir\VueFinder\VueFinderBuilder;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * STEP 1: Create Service Provider
 * 
 * File: app/Providers/VueFinderServiceProvider.php
 */
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ozdemir\VueFinder\VueFinderBuilder;
use Ozdemir\VueFinder\Actions\VueFinderActionFactory;
use League\Flysystem\Local\LocalFilesystemAdapter;

class VueFinderServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register()
    {
        // Register VueFinder Core as singleton
        $this->app->singleton('vuefinder.core', function ($app) {
            return VueFinderBuilder::create(
                [
                    'local' => new LocalFilesystemAdapter(storage_path('app/public/uploads')),
                ],
                [
                    'publicLinks' => [
                        'local://public' => config('app.url'),
                    ],
                    // Or use professional URL resolver:
                    // 'storages' => [
                    //     'local' => [
                    //         'publicBaseUrl' => config('app.url'),
                    //         'publicPrefix' => 'storage',
                    //     ],
                    // ],
                ]
            );
        });
        
        // Register Action Factory
        $this->app->singleton(VueFinderActionFactory::class, function ($app) {
            return new VueFinderActionFactory($app->make('vuefinder.core'));
        });
    }
}

/**
 * Register in config/app.php:
 * 
 * 'providers' => [
 *     // ...
 *     App\Providers\VueFinderServiceProvider::class,
 * ],
 */

/**
 * STEP 2: Create Controller
 * 
 * File: app/Http/Controllers/VueFinderController.php
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ozdemir\VueFinder\Actions\VueFinderActionFactory;

class VueFinderController extends Controller
{
    /**
     * Inject the action factory via constructor
     */
    public function __construct(
        private VueFinderActionFactory $actionFactory
    ) {}
    
    /**
     * List files in a directory
     * 
     * GET /api/files?path=local://uploads
     */
    public function index(Request $request)
    {
        $action = $this->actionFactory
            ->setRequest($request)
            ->create('index');
        
        return $action->execute();
    }
    
    /**
     * Search files
     * 
     * GET /api/files/search?path=local://uploads&filter=*.jpg
     */
    public function search(Request $request)
    {
        $action = $this->actionFactory
            ->setRequest($request)
            ->create('search');
        
        return $action->execute();
    }
    
    /**
     * Upload file
     * 
     * POST /api/files/upload?path=local://uploads
     * Body: multipart/form-data with 'file' field
     */
    public function upload(Request $request)
    {
        $action = $this->actionFactory
            ->setRequest($request)
            ->create('upload');
        
        return $action->execute();
    }
    
    /**
     * Delete files/folders
     * 
     * POST /api/files/delete?path=local://uploads
     * Body: {"items": [{"path": "local://uploads/file.txt", "type": "file"}]}
     */
    public function delete(Request $request)
    {
        $action = $this->actionFactory
            ->setRequest($request)
            ->create('delete');
        
        return $action->execute();
    }
    
    /**
     * Create new folder
     * 
     * POST /api/files/newfolder?path=local://uploads
     * Body: {"name": "new-folder"}
     */
    public function newFolder(Request $request)
    {
        $action = $this->actionFactory
            ->setRequest($request)
            ->create('newfolder');
        
        return $action->execute();
    }
    
    /**
     * Rename file/folder
     * 
     * POST /api/files/rename?path=local://uploads
     * Body: {"name": "new-name.txt", "item": "local://uploads/old-name.txt"}
     */
    public function rename(Request $request)
    {
        $action = $this->actionFactory
            ->setRequest($request)
            ->create('rename');
        
        return $action->execute();
    }
    
    // Add more methods as needed for other actions:
    // - move, copy, download, preview, save, archive, unarchive, etc.
}

/**
 * STEP 3: Define Routes
 * 
 * File: routes/api.php or routes/web.php
 */
// use Illuminate\Support\Facades\Route;

Route::prefix('api/files')->group(function () {
    // GET routes
    Route::get('/', [VueFinderController::class, 'index'])->name('vuefinder.index');
    Route::get('/search', [VueFinderController::class, 'search'])->name('vuefinder.search');
    
    // POST routes
    Route::post('/upload', [VueFinderController::class, 'upload'])->name('vuefinder.upload');
    Route::post('/delete', [VueFinderController::class, 'delete'])->name('vuefinder.delete');
    Route::post('/newfolder', [VueFinderController::class, 'newFolder'])->name('vuefinder.newfolder');
    Route::post('/rename', [VueFinderController::class, 'rename'])->name('vuefinder.rename');
    
    // Add more routes as needed
});

/**
 * Usage from JavaScript:
 * 
 * // List files
 * fetch('/api/files?path=local://uploads')
 *   .then(response => response.json())
 *   .then(data => console.log(data));
 * 
 * // Upload file
 * const formData = new FormData();
 * formData.append('file', fileInput.files[0]);
 * fetch('/api/files/upload?path=local://uploads', {
 *   method: 'POST',
 *   body: formData
 * });
 */

/**
 * OPTIONAL: Add Middleware
 * 
 * Add authentication, rate limiting, etc.:
 */
// Route::prefix('api/files')->middleware(['auth', 'throttle:api'])->group(function () {
//     // routes...
// });

/**
 * OPTIONAL: Configuration File
 * 
 * Create config/vuefinder.php:
 */
return [
    'publicLinks' => [
        'local://public' => env('APP_URL'),
    ],
    
    // Or professional configuration:
    'storages' => [
        'local' => [
            'publicBaseUrl' => env('CDN_URL', env('APP_URL')),
            'publicPrefix' => 'uploads',
        ],
    ],
];
