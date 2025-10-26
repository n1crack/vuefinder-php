# URL Configuration Guide

## Configuration Options

### 1. Simple Mapping

```php
$config = [
    'publicLinks' => [
        'local://public' => 'https://example.com',
    ],
];

$core = VueFinderBuilder::create($storages, $config);
```

### 2. Storage-Based Configuration

```php
$config = [
    'storages' => [
        'local' => [
            'publicBaseUrl' => 'https://cdn.example.com',
            'publicPrefix' => 'uploads', // Becomes https://cdn.example.com/uploads/path
            'public' => true, // Can be set to false to exclude
        ],
        's3' => [
            'publicBaseUrl' => 'https://my-bucket.s3.amazonaws.com',
        ],
    ],
];

$core = VueFinderBuilder::create($storages, $config);
```

### 3. App-Wide Base URL

```php
$config = [
    'appUrl' => 'https://example.com',
    'storages' => [
        'local' => [
            'publicPrefix' => 'storage/local',
        ],
    ],
];

// Results in: https://example.com/storage/local/path/to/file.jpg
```

### 4. Environment-Based Configuration

```php
// .env
APP_URL=https://example.com
CDN_URL=https://cdn.example.com

// In your config
$config = [
    'appUrl' => getenv('APP_URL'),
    'storages' => [
        'local' => [
            'publicBaseUrl' => getenv('CDN_URL'),
        ],
    ],
];

$core = VueFinderBuilder::create($storages, $config);
```

### 5. Exclusions (Private Folders)

```php
$config = [
    'publicLinks' => [
        'local://public' => 'https://example.com',
    ],
    'publicExclusions' => [
        'local://private',
        'local://admin',
    ],
];

// Files in local://private won't get public URLs
```

### 6. Automatic URL Detection

```php
$config = [
    // No config needed - URLs auto-generated based on current domain
];

// Automatically uses $_SERVER['HTTP_HOST']
// https://yourdomain.com/storage/local/uploads/file.jpg
```

## Complete Example

```php
use Ozdemir\VueFinder\VueFinderBuilder;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

$storages = [
    'local' => new LocalFilesystemAdapter(__DIR__ . '/uploads'),
    's3' => new AwsS3V3Adapter($s3Client, 'bucket-name'),
];

$config = [
    'appUrl' => 'https://example.com',
    'storages' => [
        'local' => [
            'publicPrefix' => 'uploads',
            'public' => true,
        ],
        's3' => [
            'publicBaseUrl' => 'https://cdn.example.com',
        ],
    ],
    'publicExclusions' => [
        'local://private',
    ],
];

$core = VueFinderBuilder::create($storages, $config);
```

**Result:**
- `local://images/photo.jpg` → `https://example.com/uploads/images/photo.jpg`
- `local://private/secret.jpg` → No URL (excluded)
- `s3://bucket/file.jpg` → `https://cdn.example.com/bucket/file.jpg`



