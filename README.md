# VueFinder - Modern PHP File Management Backend

A production-ready, SOLID-principles-based PHP backend for file management.

## Features

✅ **SOLID Principles** - Clean, maintainable code  
✅ **Framework Agnostic** - Use with Laravel, Symfony, or plain PHP  
✅ **Type Safe** - Type hints throughout  
✅ **Testable** - Dependency injection makes testing easy  
✅ **Simple Setup** - One-line builder  
✅ **Professional URL Resolver** - Smart URL generation  
✅ **17 Actions** - Complete file management API  

## Quick Start

### Option 1: Simple Usage (Plain PHP)

```php
require 'vendor/autoload.php';

use Ozdemir\VueFinder\VueFinder;
use League\Flysystem\Local\LocalFilesystemAdapter;

$vueFinder = new VueFinder([
    'local' => new LocalFilesystemAdapter(__DIR__ . '/uploads'),
]);

$vueFinder->init([
    'publicLinks' => ['local://public' => 'https://example.com'],
]);

// Use query parameters:
// GET  /index.php?q=index&path=local://uploads
// POST /index.php?q=upload&path=local://uploads
```

### Option 2: Framework Integration

```php
use Ozdemir\VueFinder\VueFinderBuilder;
use Ozdemir\VueFinder\Actions\VueFinderActionFactory;

// One-line setup!
$core = VueFinderBuilder::create(
    ['local' => new LocalFilesystemAdapter(__DIR__ . '/uploads')],
    ['publicLinks' => ['local://public' => 'https://example.com']]
);

$factory = new VueFinderActionFactory($core);

// In your controller:
$action = $factory->setRequest($request)->create('index');
return $action->execute();
```

## Available Actions

- `index` - List files and directories
- `search` - Search files with filters
- `upload` - Upload files
- `delete` - Delete files/folders
- `rename` - Rename files/folders
- `move` - Move files/folders
- `copy` - Copy files/folders
- `create-folder` - Create folder
- `create-file` - Create file
- `download` - Download files
- `preview` - Preview files
- `save` - Save file content
- `archive` - Create ZIP archives
- `unarchive` - Extract ZIP files

## Professional URL Configuration

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

## Examples

- `example-plain-php.php` - Plain PHP integration
- `example-laravel.php` - Laravel integration

## Documentation

- `README.md` - This file
- `SIMPLE_SETUP.md` - Detailed setup guide
- `URL_CONFIGURATION.md` - URL resolver configuration

## Architecture

```
src/
├── VueFinder.php              # Main entry (backwards compatible)
├── VueFinderCore.php          # Core framework-agnostic
├── VueFinderBuilder.php       # Auto-setup builder
├── Action/                     # 17 action handlers
├── Service/                    # Business logic (4 services)
├── Interface/                  # Contracts (5 interfaces)
├── Exception/                  # Custom exceptions
└── Trait/                      # Reusable functionality
```

## Installation

```bash
composer require ozdemir/vuefinder
```

## Requirements

- PHP 8.0+
- Composer
- League Flysystem
- Symfony HTTP Foundation

## License

MIT
