<?php

namespace Wwy\FileSystem\Uploader;

class Uploader
{
    /**
     * 获取上传类
     *
     * @param array $config
     * @return mixed
     */
    public static function getAdapter(array $config)
    {
        $type = $config['type'];

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

        return $adapter;
    }
}
