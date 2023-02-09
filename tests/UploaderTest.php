<?php

namespace Wwy\FileSystem\Uploader;

use PHPUnit\Framework\TestCase;
use League\Flysystem\FilesystemAdapter;

class UploaderTest extends TestCase
{
    public function testGetAdapter()
    {
        // $config = json_decode('{"type": "cos", "bucket": "lanyun-v3", "prefix": "/", "endpoint": "oss-cn-beijing.aliyuncs.com", "max_size": 1000, "expire_time": 60, "callback_url": "http://huoyun.wwy2121.top/api/upload/callback", "access_key_id": "LTAI4G319rwzMR52Hjw4JcA9", "access_key_secret": "2yKU7ILs8BRkv0cfsfEQYlCHvePbHd"}', true);
        $config = json_decode('{"type": "tencent", "tencent": {"app_id": "1300854817", "bucket": "file-1300854817", "prefix": "/", "region": "ap-beijing", "secret_id": "AKIDqzvc4IL5ap46sRr11lEerna6OQChPlwr", "secret_key": "rUqvkdGchV5QUHK8gyzmJZYprc7LM6an", "callback_url": "http://huoyun.wwy2121.top/api/upload/callback"}, "max_size": 1000, "expire_time": 60}', true);
        $adapter = Uploader::getAdapter($config);

        $this->assertTrue($adapter instanceof FilesystemAdapter, '验证失败');
    }
}