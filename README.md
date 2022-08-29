# vuefinder-php
php serverside library for vuefinder

<img width="690" alt="image" src="https://user-images.githubusercontent.com/712404/187087831-a00b2f01-cd6c-4349-8ab2-7e9d76d22213.png">

frontend (vue3) : https://github.com/n1crack/vuefinder

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









