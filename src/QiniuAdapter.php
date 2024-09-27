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
    ) {
    }

    public function getTokenConfig($type = null, ?string $key = null, ?array $policy = null, ?string $strictPolice = null)
    {
        $allow = Uploader::getAllowType($type);

        $body = [
            'type' => $allow['type'],
            'key' => '$(key)',
            'hash' => '$(etag)',
            'url' => rtrim($this->domain) . '/$(key)'
        ];

        if ($allow['type'] === 'image') {
            $body['w'] = '$(imageInfo.width)';
            $body['h'] = '$(imageInfo.height)';
        }

        $basePolicy = [
            'scope' => $this->bucket . ':' . $this->prefix,
            'isPrefixalScope' => 1,
            'callbackUrl' => $this->normalizeHost($this->callback_url),
            'callbackBodyType' => 'application/json',
            'callbackBody' => json_encode($body, JSON_UNESCAPED_UNICODE),
            'fsizeLimit' => (int) $allow['max_size'],
            'insertOnly' => 1,
            'forceSaveKey' => true,
            'saveKey' => ltrim($this->prefix) . '$(etag)'
        ];

        if ($allow['mimetypes'] && $allow['mimetypes'] !== '*') {
            $basePolicy['mimeLimit'] = is_array($allow['mimetypes']) ? implode(';', $allow['mimetypes']) : $allow['mimetypes'];
        }

        if (!is_null($policy)) {
            $basePolicy = array_merge($basePolicy, $policy);
        }

        $token = $this->getUploadToken($key, $this->expire_time, $basePolicy, $strictPolice);

        return [
            'prefix' => $this->prefix,
            'token' => $token,
            'expire_time' => time() + $this->expire_time,
            'domain' => $this->domain,
            'max_size' => $allow['max_size'],
            'mime_types' => $allow['mimetypes']
        ];
    }

    public function normalizeHost($domain): string
    {
        if (0 !== stripos($domain, 'https://') && 0 !== stripos($domain, 'http://')) {
            $domain = "http://{$domain}";
        }

        return rtrim($domain, '/') . '/';
    }
}
