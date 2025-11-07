# VueFinder PHP 4.0

**Modern PHP Backend for VueFinder File Manager**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

VueFinder is a powerful, modern file management system that provides a beautiful web interface for managing files and folders. This PHP backend powers VueFinder, giving you complete control over file operations like upload, download, rename, move, copy, archive, and more.

Whether you're building a content management system, a file sharing platform, or need file management capabilities in your application, VueFinder PHP provides a robust, production-ready solution that works with any PHP framework or plain PHP.

## What is VueFinder?

VueFinder is a full-featured file manager that allows users to:
- Browse and organize files and folders
- Upload files with drag & drop support
- Search files with advanced filters
- Edit, rename, move, and copy files
- Create and extract ZIP archives
- Preview files directly in the browser
- Generate public URLs for files
- And much more!

This PHP package is the backend API that handles all file operations. It's designed to work seamlessly with the VueFinder frontend, but can also be used standalone for building custom file management solutions.

## Why Choose VueFinder PHP?

- **Easy to Use** - Get started in minutes with simple setup  
- **Framework Agnostic** - Works with Laravel, Symfony, or plain PHP  
- **Production Ready** - Built with type safety and error handling  
- **Flexible Storage** - Support for local files, cloud storage, and more  
- **Complete API** - 17 actions covering all file management needs  
- **Modern Architecture** - Clean, maintainable, and testable code  
- **CDN Support** - Built-in URL resolver for CDN integration  
- **Secure** - Private folder exclusions and access control

## Installation

Install via Composer:

```bash
composer require ozdemir/vuefinder
```

### Requirements

- PHP 8.0 or higher
- Composer
- League Flysystem 3.x
- Symfony HTTP Foundation 6.x  

## Quick Start

### Basic Setup (Plain PHP)

The simplest way to get started:

```php
require 'vendor/autoload.php';

use Ozdemir\VueFinder\VueFinder;
use League\Flysystem\Local\LocalFilesystemAdapter;

// Create VueFinder instance with your storage
$vueFinder = new VueFinder([
    'local' => new LocalFilesystemAdapter(__DIR__ . '/uploads'),
]);

// Initialize with configuration
$vueFinder->init([
    'publicLinks' => ['local://public' => 'https://example.com'],
]);

// That's it! VueFinder will handle requests automatically:
// GET  /index.php?q=index&path=local://uploads
// POST /index.php?q=upload&path=local://uploads
```

### Framework Integration (Laravel, Symfony, etc.)

For framework integration, use the builder pattern:

```php
use Ozdemir\VueFinder\VueFinderBuilder;
use Ozdemir\VueFinder\Actions\VueFinderActionFactory;
use League\Flysystem\Local\LocalFilesystemAdapter;

// One-line setup!
$core = VueFinderBuilder::create(
    ['local' => new LocalFilesystemAdapter(__DIR__ . '/uploads')],
    ['publicLinks' => ['local://public' => 'https://example.com']]
);

// In your controller:
$factory = new VueFinderActionFactory($core);
$action = $factory->setRequest($request)->create('index');
return $action->execute();
```

## Available Actions

VueFinder PHP provides a complete set of file management actions:

| Action | Description |
|--------|-------------|
| `index` | List files and directories in a folder |
| `search` | Search files with filters and size categorization |
| `upload` | Upload files with validation |
| `delete` | Delete files or folders (with recursive support) |
| `rename` | Rename files and folders |
| `move` | Move files/folders to new locations |
| `copy` | Copy files/folders |
| `create-folder` | Create new directories |
| `create-file` | Create new text files |
| `download` | Download files with proper headers |
| `preview` | Preview files in browser |
| `save` | Save file content |
| `archive` | Create ZIP archives from files/directories |
| `unarchive` | Extract ZIP archives |

All actions are implemented as separate classes, making the codebase maintainable and easy to extend.

## Configuration

### URL Configuration

Configure public URLs for your files, including CDN support:

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
    'publicExclusions' => ['local://private'], // Exclude private folders
];

$core = VueFinderBuilder::create($storages, $config);
```

### Storage Configuration

VueFinder supports multiple storage backends through Flysystem:

```php
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

$storages = [
    'local' => new LocalFilesystemAdapter(__DIR__ . '/uploads'),
    's3' => new AwsS3V3Adapter($s3Client, 'my-bucket'),
    // Add more storage adapters as needed
];
```

## Examples

### Example: File Upload Endpoint

```php
$core = VueFinderBuilder::create($storages, $config);
$factory = new VueFinderActionFactory($core);

// Handle file upload
$action = $factory->setRequest($request)->create('upload');
$response = $action->execute();
```

### Example: List Files

```php
// GET /api/files?q=index&path=local://documents
$action = $factory->setRequest($request)->create('index');
return $action->execute();
```

### Example Laravel Project

For a complete Laravel implementation example, check out the [VueFinder Laravel API](https://github.com/n1crack/vuefinder-api-php) project. This example demonstrates how to integrate VueFinder PHP into a Laravel application with REST API endpoints.

## Migration from v3

VueFinder PHP 4.0 maintains API compatibility with v3. The query parameter method (`?q=action`) continues to work as before, ensuring smooth migration.

## Contributing

Contributions are welcome! Please ensure your code follows clean architecture principles and includes appropriate tests.

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Learn More

For more information about VueFinder, visit [vuefinder.ozdemir.be](https://vuefinder.ozdemir.be).

---
