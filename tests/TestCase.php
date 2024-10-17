<?php

namespace Lanyunit\FileSystem\Uploader\Tests;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Lanyunit\FileSystem\Uploader\UploaderServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app)
    {
        // make sure, our .env file is loaded
        $app->useEnvironmentPath(__DIR__.'/../');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);

        config()->set('filesystems.disks.uploader', [
            'driver' => 'uploader',
            'type' => 'local',
            'max_size' => 30,
            'expire_time' => 30 * 60,
            'callback_url' => '',
            'prefix' => 'image',
            'local' => [],
            'qiniu' => [
                'bucket' => 'wwy2121',
                'domain' => 'assets.wwy2121.top',
                'access_key' => env('QINIU_ACCESS_KEY'),
                'secret_key' => env('QINIU_SECRET_KEY'),
            ],
            'aliyun' => [
                'bucket' => 'suibian3131',
                'isCName' => false,
                'endpoint' => 'oss-cn-shanghai.aliyuncs.com',
                'access_key_id' => env('ALIYUN_ACCESS_KEY_ID'),
                'access_key_secret' => env('ALIYUN_ACCESS_KEY_SECRET'),
            ],
            'tencent' => [
                'app_id' => '1300854817',
                'bucket' => 'file-1300854817',
                'region' => 'ap-beijing',
                'secret_id' => env('TENCENT_SECRET_ID'),
                'secret_key' => env('TENCENT_SECRET_KEY'),
            ],
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            UploaderServiceProvider::class,
        ];
    }
}
