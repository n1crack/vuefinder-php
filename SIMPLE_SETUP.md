# VueFinder - Simple Setup Guide

## Quick Start

### Plain PHP - Simplest Way

```php
<?php

require 'vendor/autoload.php';

use Ozdemir\VueFinder\VueFinderBuilder;
use Ozdemir\VueFinder\Actions\VueFinderActionFactory;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;

// ONE LINE SETUP! 🎉
$core = VueFinderBuilder::create(
    ['local' => new LocalFilesystemAdapter(__DIR__ . '/uploads')],
    ['publicLinks' => ['local://public' => 'https://example.com']]
);

// Create action factory
$actionFactory = new VueFinderActionFactory($core);

// Use it!
$request = Request::createFromGlobals();
$action = $actionFactory->setRequest($request)->create('index');
$response = $action->execute();
$response->send();
```

**That's it!** No need to manually create services, builders do everything automatically.

## Using with Laravel

### 1. Service Provider

```php
// app/Providers/VueFinderServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ozdemir\VueFinder\VueFinderBuilder;
use Ozdemir\VueFinder\Actions\VueFinderActionFactory;

class VueFinderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('vuefinder.core', function ($app) {
            return VueFinderBuilder::create(
                ['local' => new \League\Flysystem\Local\LocalFilesystemAdapter(storage_path('app/public'))],
                ['publicLinks' => config('vuefinder.publicLinks', [])]
            );
        });
        
        $this->app->singleton(VueFinderActionFactory::class, function ($app) {
            return new VueFinderActionFactory($app->make('vuefinder.core'));
        });
    }
}
```

### 2. Controller

```php
// app/Http/Controllers/FilesController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ozdemir\VueFinder\Actions\VueFinderActionFactory;

class FilesController extends Controller
{
    public function __construct(private VueFinderActionFactory $actionFactory) {}
    
    public function index(Request $request)
    {
        $action = $this->actionFactory
            ->setRequest($request)
            ->create('index');
        
        return $action->execute();
    }
    
    public function upload(Request $request)
    {
        $action = $this->actionFactory
            ->setRequest($request)
            ->create('upload');
        
        return $action->execute();
    }
    
    // Add more methods for other actions...
}
```

### 3. Routes

```php
// routes/web.php

Route::get('/api/files', [FilesController::class, 'index']);
Route::post('/api/files/upload', [FilesController::class, 'upload']);
Route::post('/api/files/delete', [FilesController::class, 'delete']);
```

## Professional URL Configuration

Instead of simple `publicLinks` mapping, use the professional approach:

```php
$config = [
    'storages' => [
        'local' => [
            'publicBaseUrl' => 'https://cdn.example.com',
            'publicPrefix' => 'uploads',
        ],
        's3' => [
            'publicBaseUrl' => 'https://my-bucket.s3.amazonaws.com',
        ],
    ],
    'publicExclusions' => ['local://private'],
];

$core = VueFinderBuilder::create($storages, $config);
```

See `URL_CONFIGURATION.md` for complete URL configuration options.

## Available Actions

- `index` - List files and directories
- `search` - Search files
- `upload` - Upload files
- `delete` - Delete files/folders
- `rename` - Rename files/folders
- `move` - Move files/folders
- `copy` - Copy files/folders
- `newfolder` - Create folder
- `newfile` - Create file
- `download` - Download files
- `preview` - Preview files
- `save` - Save file content
- `archive` - Create ZIP archives
- `unarchive` - Extract ZIP files

## Custom Routing Example

```php
// Your own simple router

function route($path, $method) {
    $routes = [
        'GET /api/files' => 'index',
        'GET /api/files/search' => 'search',
        'POST /api/files/upload' => 'upload',
        'POST /api/files/delete' => 'delete',
    ];
    
    $key = "$method $path";
    return $routes[$key] ?? null;
}

// Use it
$request = Request::createFromGlobals();
$actionName = route($request->getPathInfo(), $request->getMethod());

if ($actionName) {
    $action = $actionFactory->setRequest($request)->create($actionName);
    $response = $action->execute();
    $response->send();
}
```

## Benefits

✅ **One-Line Setup** - `VueFinderBuilder::create()` does everything  
✅ **Framework Agnostic** - Works with Laravel, Symfony, or plain PHP  
✅ **Easy to Test** - Dependencies can be mocked  
✅ **Flexible** - Define your own routing  
✅ **Professional** - Production-ready URL resolver  

## See Examples

- `example-plain-php.php` - Plain PHP integration
- `example-laravel.php` - Laravel integration
