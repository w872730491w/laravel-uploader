<?php

namespace Lanyunit\FileSystem\Uploader;

class Uploader
{
    /**
     * 获取上传类
     *
     * @param array $config
     * @return mixed
     */
    public static function getAdapter(array $baseConfig)
    {
        $type = $baseConfig['type'];
        $config = $baseConfig[$type];
        $config['max_size'] = $baseConfig['max_size'];
        $config['expire_time'] = $baseConfig['expire_time'];

        if ($type === 'aliyun') {
            $root = $config['prefix'] ?? null;

            $adapter = new AliyunOssAdapter(
                $config['access_key_id'],
                $config['access_key_secret'],
                $config['endpoint'],
                $config['bucket'],
                $config['isCName'],
                $root,
                $config['callback_url'],
                $config['expire_time'],
                $config['max_size'] * 1024 * 1024,
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
            ];

            $adapter = new TencentCosAdapter($config);
        }

        if ($type === 'qiniu') {
            $adapter = new QiniuAdapter(
                $config['access_key'],
                $config['secret_key'],
                $config['bucket'],
                $config['domain'],
                $config['expire_time']
            );
        }

        return $adapter;
    }
}
