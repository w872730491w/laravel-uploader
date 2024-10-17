<?php

namespace Lanyunit\FileSystem\Uploader;

use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UploaderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-uploader')
            ->hasConfigFile();
    }

    public function bootingPackage()
    {
        app('filesystem')->extend('uploader', function ($app, $config) {
            $adapter = Uploader::getAdapter($config);

            return new FilesystemAdapter(new Filesystem($adapter), $adapter, $config);
        });
    }
}
