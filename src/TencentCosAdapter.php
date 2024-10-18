<?php

namespace Lanyunit\FileSystem\Uploader;

use League\Flysystem\PathPrefixer;
use Overtrue\Flysystem\Cos\CosAdapter;

class TencentCosAdapter extends CosAdapter
{
    public function getTokenConfig($type = null, string $path = '/', array $customData = [])
    {
        $allow = Uploader::getAllowType($type);

        $path = (new PathPrefixer($this->config['prefix'], DIRECTORY_SEPARATOR))->prefixPath($path);

        $config = \array_merge([
            'url' => 'https://sts.tencentcloudapi.com/', // url和domain保持一致
            'domain' => 'sts.tencentcloudapi.com', // 域名，非必须，默认为 sts.tencentcloudapi.com
            'proxy' => '',
            'secretId' => $this->config['secret_id'], // 固定密钥,若为明文密钥，请直接以'xxx'形式填入，不要填写到getenv()函数中
            'secretKey' => $this->config['secret_key'], // 固定密钥,若为明文密钥，请直接以'xxx'形式填入，不要填写到getenv()函数中
            'bucket' => $this->config['bucket'], // 换成你的 bucket
            'region' => $this->config['region'], // 换成 bucket 所在园区
            'durationSeconds' => $this->config['expire_time'], // 密钥有效期
            'allowPrefix' => ["$path*"], // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
            // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
            'allowActions' => [
                // 简单上传
                'name/cos:PutObject',
                'name/cos:PostObject',
                // 分片上传
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload',
            ],
        ], $customData);

        $tempKeys = (new \QCloud\COSSTS\Sts)->getTempKeys($config);

        $res = [
            'callback_url' => $this->config['callback_url'],
            'bucket' => $config['bucket'], // 换成你的 bucket
            'region' => $config['region'], // 换成 bucket 所在园区
            'path' => $path,
            'tempKeys' => $tempKeys,
            'mime_types' => $allow['mimetypes'],
            'max_size' => $allow['max_size'],
            'expire_time' => $tempKeys['expiredTime'],
            'auth' => encrypt([
                'token' => $tempKeys['credentials']['sessionToken'],
                'maxSize' => $allow['max_size'],
                'mimeTypes' => $allow['mimetypes'],
            ]),
        ];

        return $res;
    }
}
