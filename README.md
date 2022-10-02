# vuefinder-php
php serverside library for vuefinder

![ezgif-1-b902690b76](https://user-images.githubusercontent.com/712404/193141338-8d5f726f-da1a-4825-b652-28e4007493db.gif)


frontend (vue3) : https://github.com/n1crack/vuefinder

## Installation 
```
composer require ozdemir/vuefinder-php
```
## Usage
```php
require '../vendor/autoload.php';

use Ozdemir\VueFinder\Vuefinder;
use League\Flysystem\Local\LocalFilesystemAdapter;

// Set VueFinder class
$vuefinder = new VueFinder([
    'local' => new LocalFilesystemAdapter(dirname(__DIR__).'/storage'),
    'test' =>  new LocalFilesystemAdapter(dirname(__DIR__).'/test'),
]);


$config = [
    'publicLinks' => [
        'local://public' => 'http://example.test',
    ],
];

// Perform the class
$vuefinder->init($config);
```









