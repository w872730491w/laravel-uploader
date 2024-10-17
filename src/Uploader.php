<?php

namespace Lanyunit\FileSystem\Uploader;

use Lanyunit\FileSystem\Uploader\Exception\UploaderException;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

class Uploader
{
    /**
     * 获取上传类
     *
     * @return AliyunOssAdapter|LocalAdapter|QiniuAdapter|TencentCosAdapter
     *
     * @throws \Lanyunit\FileSystem\Uploader\Exception\UploaderException
     */
    public static function getAdapter(array $baseConfig)
    {
        $type = $baseConfig['type'];

        if (! in_array($type, ['aliyun', 'tencent', 'local', 'qiniu'])) {
            throw new UploaderException('不支持此类型');
        }

        $config = $baseConfig[$type];
        $config['expire_time'] = $baseConfig['expire_time'];
        $config['callback_url'] = $baseConfig['callback_url'];
        $config['prefix'] = $baseConfig['prefix'];

        if ($type === 'aliyun') {
            $root = isset($config['prefix']) ? ltrim($config['prefix'], '/') : null;

            $adapter = new AliyunOssAdapter(
                $config['access_key_id'],
                $config['access_key_secret'],
                $config['endpoint'],
                $config['bucket'],
                $config['isCName'],
                $root,
                $config['callback_url'],
                $config['expire_time'],
                []
            );
        }

        if ($type === 'tencent') {
            $config = [
                'app_id' => $config['app_id'],
                'secret_id' => $config['secret_id'],
                'secret_key' => $config['secret_key'],
                'region' => $config['region'],
                'bucket' => $config['bucket'],
                // 可选，如果 bucket 为私有访问请打开此项
                'signed_url' => false,
                // 可选，是否使用 https，默认 false
                'use_https' => true,
                // 可选，自定义域名
                'domain' => $config['domain'] ?? null,
                // 可选，使用 CDN 域名时指定生成的 URL host
                'cdn' => $config['cdn'] ?? null,
                'prefix' => $config['prefix'] ?? '/',
                'expire_time' => $config['expire_time'],
                'callback_url' => $config['callback_url'],
            ];

            $adapter = new TencentCosAdapter($config);
        }

        if ($type === 'qiniu') {
            $adapter = new QiniuAdapter(
                $config['access_key'],
                $config['secret_key'],
                $config['bucket'],
                $config['domain'],
                $config['expire_time'],
                $config['prefix'],
                $config['callback_url'],
            );
        }

        if ($type === 'local') {
            $localConfig = config('filesystems.disks.public');

            $visibility = PortableVisibilityConverter::fromArray(
                $localConfig['permissions'] ?? [],
                $localConfig['directory_visibility'] ?? $localConfig['visibility'] ?? Visibility::PRIVATE
            );

            $links = ($localConfig['links'] ?? null) === 'skip'
                ? \League\Flysystem\Local\LocalFilesystemAdapter::SKIP_LINKS
                : \League\Flysystem\Local\LocalFilesystemAdapter::DISALLOW_LINKS;

            $adapter = new LocalAdapter(
                $localConfig['root'],
                $visibility,
                $localConfig['lock'] ?? LOCK_EX,
                $links,
                null,
                false,
                $config['expire_time'],
                $config['prefix'],
                $config['callback_url'],
            );
        }

        if (! isset($adapter)) {
            throw new UploaderException('不支持该类型上传');
        }

        return $adapter;
    }

    /**
     * 检查类型
     *
     * @param  mixed  $type
     * @return array
     *
     * @throws \Lanyunit\FileSystem\Uploader\Exception\UploaderException
     */
    public static function getAllowType(?string $type = null)
    {
        if (is_null($type)) {
            $type = request()->post('type');
        }

        $allow = config('uploader.allow');

        if (! isset($allow[$type])) {
            throw new UploaderException('允许上传的类型不存在');
        }

        $config = $allow[$type];

        return [
            'type' => $type,
            'mimetypes' => $config['mime'],
            'max_size' => (int) ($config['max_size'] * 1024 * 1024),
        ];
    }
}
