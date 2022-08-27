# vuefinder-php
php serverside library for vuefinder

## Installation 
```
composer require ozdemir/vuefinder-php
```
## Usage
```php
require '../vendor/autoload.php';

use Ozdemir\Vuefinder\Vuefinder;
use League\Flysystem\Local\LocalFilesystemAdapter;

// Set VueFinder class
$vuefinder = new VueFinder([
    'local' => new LocalFilesystemAdapter(dirname(__DIR__).'/storage'),
    'test' =>  new LocalFilesystemAdapter(dirname(__DIR__).'/test'),
]);

$config = [
    'publicPaths' => [
        'public' => 'http://example.test',
    ],
];

// Perform the class
$vuefinder->init($config);
```









