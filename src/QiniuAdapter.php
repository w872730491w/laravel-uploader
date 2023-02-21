<?php

namespace Lanyunit\FileSystem\Uploader;

use League\Flysystem\PathPrefixer;
use Overtrue\Flysystem\Qiniu\QiniuAdapter as QiniuQiniuAdapter;

class QiniuAdapter extends QiniuQiniuAdapter
{
    public function __construct(
        protected string $accessKey,
        protected string $secretKey,
        protected string $bucket,
        protected string $domain,
        protected $expire_time,
        protected string $prefix,
        protected string $callback_url,
        protected string $max_size
    ) {
    }

    public function getTokenConfig(?string $key = null, ?array $policy = null, ?string $strictPolice = null)
    {
        $basePolice = [
            'scope' => $this->bucket . ':' . $this->prefix,
            'isPrefixalScope' => 1,
            'callbackUrl' => $this->callback_url,
            'callbackBodyType' => 'application/json',
            'returnBody' => '{ "key": $(key), "hash": $(etag), "w": $(imageInfo.width), "h": $(imageInfo.height) }',
            'fsizeLimit' => $this->max_size,
            'forceSaveKey' => true,
            'saveKey' => '$(etag)'
        ];

        if (!is_null($policy)) {
            $basePolice = array_merge($basePolice, $policy);
        }

        $token = $this->getUploadToken($key, $this->expire_time, $basePolice, $strictPolice);

        return [
            'key' => $key,
            'token' => $token,
            'expire_time' => time() + $this->expire_time,
            'domain' => $this->domain
        ];
    }
}
