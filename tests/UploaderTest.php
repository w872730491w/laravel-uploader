<?php

namespace Wwy\FileSystem\Uploader;

use Lanyunit\FileSystem\Uploader\Uploader;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

class UploaderTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        // make sure, our .env file is loaded
        $app->useEnvironmentPath(__DIR__ . '/../workbench');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);
        parent::getEnvironmentSetUp($app);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        tap($app['config'], function (Repository $config) {
            $config->set('uploader.allow', [
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
            ]);
        });
    }

    protected function getPackageProviders($app)
    {
        return ['Lanyunit\FileSystem\Uploader\UploaderServiceProvider'];
    }

    public function testGetAdapter()
    {
        $data = [];
        $adapter = Uploader::getAdapter($data);
        $data = $adapter->getTokenConfig('image');
        $this->assertIsArray($data);
    }
}