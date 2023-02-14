<?php

namespace Lanyunit\FileSystem\Uploader;

use Overtrue\Flysystem\Qiniu\QiniuAdapter as QiniuQiniuAdapter;

class QiniuAdapter extends QiniuQiniuAdapter
{
    public function __construct(
        protected string $accessKey,
        protected string $secretKey,
        protected string $bucket,
        protected string $domain,
        protected $expire_time
    ) {
    }

    public function getTokenConfig(?string $key = null, ?array $policy = null, ?string $strictPolice = null)
    {
        return $this->getUploadToken($key, $this->expire_time, $policy, $strictPolice);
    }
}
