# vuefinder-php
php serverside library for vuefinder

## Installation 
```
composer require ozdemir/vuefinder-php
```
## Usage
```php
use Ozdemir\Vuefinder\Vuefinder;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

// Set Filesystem Storage 
$adapter = new Local(\dirname(__DIR__).'/storage');
$storage = new Filesystem($adapter);

// Set VueFinder class
$vuefinder = new VueFinder($storage);

// Perform the class
$vuefinder->init();
```