# VueFinder PHP 4.0

**VueFinder PHP Backend**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

VueFinder PHP 4.0 is a production-ready PHP backend for the [VueFinder 4.0 frontend](https://github.com/ozdemirburak/vuefinder). Built with clean architecture, dependency injection, and comprehensive type safety for reliable file management operations.

## 🎯 VueFinder 4.0 Frontend Compatibility

VueFinder PHP 4.0 is fully compatible with VueFinder 4.0 frontend. The API follows the same specification, ensuring seamless integration between the frontend and backend components.

## ✨ What's New in 4.0

**Architecture**
- PHP 8+ with strict type safety
- Clean architecture with clear separation of concerns
- Framework-agnostic design for maximum flexibility
- Dependency injection for full testability

**Professional URL Management**
- Advanced URL resolver with multiple configuration strategies
- Support for CDN integration and storage-specific URLs
- Environment-aware URL generation
- Private folder exclusions

**Improved Developer Experience**
- One-line setup with VueFinderBuilder
- Clear separation of concerns with dedicated action classes
- Comprehensive exception handling
- Full IDE autocompletion support

**Production Ready**
- Type-safe throughout
- Easy to test and mock
- Well-documented codebase
- Follows PHP best practices

## Features

✅ **Clean Architecture** - Maintainable, extensible code structure  
✅ **Framework Agnostic** - Works seamlessly with Laravel, Symfony, or plain PHP  
✅ **Type Safe** - Comprehensive type hints and strict typing throughout  
✅ **Testable** - Dependency injection enables easy unit testing  
✅ **Simple Setup** - One-line builder for instant configuration  
✅ **Professional URL Resolver** - Enterprise-grade URL generation with multiple strategies  
✅ **17 Actions** - Complete file management API covering all use cases  
✅ **VueFinder 4.0 Compatible** - Full compatibility with VueFinder 4.0 frontend  

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

VueFinder PHP 4.0 provides 17 comprehensive actions for complete file management:

- `index` - List files and directories
- `search` - Search files with filters and size categorization
- `upload` - Upload files with validation
- `delete` - Delete files/folders (recursive support)
- `rename` - Rename files/folders
- `move` - Move files/folders to new locations
- `copy` - Copy files/folders
- `create-folder` - Create new directories
- `create-file` - Create new files
- `download` - Download files with proper headers
- `preview` - Preview files in browser
- `save` - Save file content
- `archive` - Create ZIP archives from files/directories
- `unarchive` - Extract ZIP archives
- Additional utility actions

All actions are implemented as separate classes following the Single Responsibility Principle, making the codebase highly maintainable and testable.

## URL Configuration

VueFinder PHP 4.0 includes a flexible URL resolver supporting multiple configuration strategies:

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

For more advanced URL configuration, see the examples in the repository.

## Installation

```bash
composer require ozdemir/vuefinder
```

## Requirements

- PHP 8.0 or higher
- Composer
- League Flysystem 3.x
- Symfony HTTP Foundation 6.x

## Migration from v3

VueFinder PHP 4.0 maintains API compatibility with v3. The query parameter method (`?q=action`) continues to work as before, ensuring smooth migration.

## Contributing

Contributions are welcome! Please ensure your code follows clean architecture principles and includes appropriate tests.

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Related Projects

- [VueFinder 4.0 Frontend](https://github.com/ozdemirburak/vuefinder) - Vue.js file manager frontend

---
