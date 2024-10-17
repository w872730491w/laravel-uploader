# Laravel Uploader

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lanyunit/laravel-uploader.svg?style=flat-square)](https://packagist.org/packages/lanyunit/laravel-uploader)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/w872730491w/laravel-uploader/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/w872730491w/laravel-uploader/actions/workflows/run-tests.yml?query=branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/w872730491w/laravel-uploader/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square)](https://github.com/w872730491w/laravel-uploader/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/lanyunit/laravel-uploader.svg?style=flat-square)](https://packagist.org/packages/lanyunit/laravel-uploader)

Integrate `local`, `Tencent Cloud COS`, `Alibaba Cloud OSS`, and `Qiniu Cloud` uploads

## Installation

You can install the package via composer:

```bash
composer require lanyunit/laravel-uploader
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="uploader-config"
```

This is the contents of the published config file:

```php
return [
    'allow' => [
        'audio' => [
            'mime' => ['audio/mpeg'],
            'max_size' => 30
        ],
        'video' => [
            'mime' => ['video/mp4', 'video/quicktime', 'video/mpeg', 'video/avi'],
            'max_size' => 30
        ],
        'files' => [
            'mime' => '*',
            'max_size' => 30
        ],
        'image' => [
            'mime' => ['image/jpeg', 'image/png', 'image/gif'],
            'max_size' => 30
        ],
    ]
];
```

## Configuration

1. Add a new disk to your `config/filesystems.php` config:

```php
<?php

return [
   'disks' => [
       //...
       'uploader' => [
            'driver' => 'uploader',
            'type' => 'local', // local tencent qiniu aliyun
            'max_size' => 30, // 30MB
            'expire_time' => 30 * 60, // seconds
            'callback_url' => '', // upload callback url
            'prefix' => 'image', // upload directory prefix
            'qiniu' => [
                "bucket" => "your-bucket" // bucket name,
                "domain" => "your-domain.com" // assets domain,
                "access_key" => env('QINIU_ACCESS_KEY'),
                "secret_key" => env('QINIU_SECRET_KEY')
            ],
            'aliyun' => [
                "bucket" => "your-bucket", // bucket name,
                "isCName" => false, // is custom domain
                "endpoint" => "oss-cn-shanghai.aliyuncs.com", // your bucket endpoint
                "access_key_id" => env('ALIYUN_ACCESS_KEY_ID'),
                "access_key_secret" => env('ALIYUN_ACCESS_KEY_SECRET')
            ],
            'tencent' => [
                "app_id" => "your-app-id", // tencent app id
                "bucket" => "your-bucket", // bucket name,
                "region" => "ap-beijing", // bucket region
                "secret_id" => env('TENCENT_SECRET_ID'),
                "secret_key" => env('TENCENT_SECRET_KEY')
            ]
       ],
       //...
    ]
];
```

2. Set callback route

```php
<?php

use Illuminate\Support\Facades\Route;
use Lanyunit\FileSystem\Uploader\Controllers\Callback;

Route::post('callback', [Callback::class, 'index']);
```

## Usage

```php
$storage = app('filesystem')->disk('uploader');
// Web Direct Transfer
dd($storage->getAdapter()->getTokenConfig('image'));
```

[Full API documentation.](http://flysystem.thephpleague.com/api/)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [wwy](https://github.com/w872730491w)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
