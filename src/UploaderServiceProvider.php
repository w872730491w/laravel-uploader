<?php

namespace Lanyunit\FileSystem\Uploader;

use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter;

class UploaderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->registerRoutes();

        app('filesystem')->extend('uploader', function ($app, $config) {
            $adapter = Uploader::getAdapter($config);
            return new FilesystemAdapter(new Filesystem($adapter), $adapter, $config);
        });
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::group([
            'namespace' => 'Lanyunit\FileSystem\Uploader\Controllers',
            'prefix' => 'api/upload',
        ], function () {
            Route::post('callback', 'Callback@index');
        });
    }
}
