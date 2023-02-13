<?php

namespace Lanyunit\FileSystem\Uploader;

use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter;

class UploaderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        app('filesystem')->extend('uploader', function ($app, $config) {
            $config['driver'] = 'uploader';
            $adapter = Uploader::getAdapter($config);
            return new FilesystemAdapter(new Filesystem($adapter), $adapter, $config);
        });
    }
}
