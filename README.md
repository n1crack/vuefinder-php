# vuefinder-php
php serverside library for vuefinder

![image](https://user-images.githubusercontent.com/712404/188312668-81882b14-7dcf-4144-b7bc-d3ca6a49b15c.png)

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

// todo: will be fixed.. 
$config = [
    'publicPaths' => [
        'public' => 'http://example.test',
    ],
];

// Perform the class
$vuefinder->init($config);
```









