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
        $app->useEnvironmentPath(__DIR__ . '/../');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);

        config()->set('filesystems.disks.uploader', [
            'driver' => 'uploader',
            'type' => 'local',
            'expire_time' => 30 * 60,
            'callback_url' => '',
            'prefix' => 'test/',
            'local' => [],
            // 'qiniu' => [
            //     'bucket' => 'wwy2121',
            //     'domain' => 'assets.wwy2121.top',
            //     'access_key' => env('QINIU_ACCESS_KEY'),
            //     'secret_key' => env('QINIU_SECRET_KEY'),
            // ],
            'aliyun' => [
                'x-oss-forbid-overwrite' => (bool) env('ALIYUN_FORBID_OVERWRITE', true),
                'success_action_status' => (int) env('ALIYUN_SUCCESS_ACTION_STATUS', 200),
                'bucket' => env('ALIYUN_BUCKET', ''),
                'isCName' => (bool) env('ALIYUN_IS_CNAME', false),
                'endpoint' => env('ALIYUN_ENDPOINT', 'oss-cn-hangzhou.aliyuncs.com'),
                'access_key_id' => env('ALIYUN_ACCESS_KEY_ID', ''),
                'access_key_secret' => env('ALIYUN_ACCESS_KEY_SECRET', ''),
            ],
            'tencent' => [
                'app_id' => env('TENCENT_APP_ID', ''),
                'bucket' => env('TENCENT_BUCKET', ''),
                'region' => env('TENCENT_REGION', 'ap-beijing'),
                'secret_id' => env('TENCENT_SECRET_ID', ''),
                'secret_key' => env('TENCENT_SECRET_KEY', ''),
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
