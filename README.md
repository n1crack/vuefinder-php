# vuefinder-php
php serverside library for vuefinder

|          |           |
| ---      | ---       |
| ![image](https://user-images.githubusercontent.com/712404/191837549-229b4cc2-03bc-4f25-bd54-73eabc80fea8.png) |  ![image](https://user-images.githubusercontent.com/712404/191838344-88913a42-5613-446c-92cb-a805da9fdea9.png) |



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
    'publicLinks' => [
        'local://public' => 'http://example.test',
    ],
];

// Perform the class
$vuefinder->init($config);
```









