<?php

namespace Lanyunit\FileSystem\Uploader;

use League\Flysystem\PathPrefixer;
use Iidestiny\Flysystem\Oss\OssAdapter;

class AliyunOssAdapter extends OssAdapter
{
    /**
     * @var
     */
    protected $callBackUrl;

    /**
     * @var
     */
    protected $expire;

    /**
     * @var
     */
    protected $contentLengthRangeValue;

    /**
     * @param $accessKeyId
     * @param $accessKeySecret
     * @param $endpoint
     * @param $bucket
     * @param $isCName
     * @param $prefix
     * @param $callBackUrl
     * @param ...$params
     *
     * @throws OssException
     */
    public function __construct($accessKeyId, $accessKeySecret, $endpoint, $bucket, bool $isCName = false, string $prefix = '', $callBackUrl = '', int $expire = 30, int $contentLengthRangeValue = 1048576000, array $buckets = [], ...$params)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->endpoint = $endpoint;
        $this->bucket = $bucket;
        $this->isCName = $isCName;
        $this->prefixer = new PathPrefixer($prefix, DIRECTORY_SEPARATOR);
        $this->buckets = $buckets;
        $this->callBackUrl = $callBackUrl;
        $this->expire = $expire;
        $this->contentLengthRangeValue = $contentLengthRangeValue;
        $this->params = $params;
        $this->initClient();
        $this->checkEndpoint();
    }

    /**
     * oss 直传配置.
     *
     * @param null $callBackUrl
     *
     * @return false|string
     *
     * @throws \Exception
     */
    public function signatureConfig(string $prefix = '', $callBackUrl = null, array $customData = [], int $expire = 30, int $contentLengthRangeValue = 1048576000, array $systemData = [])
    {
        $prefix = $this->prefixer->prefixPath($prefix);

        // 系统参数
        $system = [];
        if (empty($systemData)) {
            $system = self::SYSTEM_FIELD;
        } else {
            foreach ($systemData as $key => $value) {
                if (!in_array($value, self::SYSTEM_FIELD)) {
                    throw new \InvalidArgumentException("Invalid oss system filed: $value");
                }
                $system[$key] = $value;
            }
        }

        // 自定义参数
        $callbackVar = [];
        $data = [];
        if (!empty($customData)) {
            foreach ($customData as $key => $value) {
                $callbackVar['x:'.$key] = $value;
                $data[$key] = '${x:'.$key.'}';
            }
        }

        $callbackParam = [
            'callbackUrl' => $callBackUrl,
            'callbackBody' => urldecode(http_build_query(array_merge($system, $data))),
            'callbackBodyType' => 'application/x-www-form-urlencoded',
        ];
        $callbackString = json_encode($callbackParam);
        $base64CallbackBody = base64_encode($callbackString);

        $now = time();
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);

        // 最大文件大小.用户可以自己设置
        $condition = [
            0 => 'content-length-range',
            1 => 0,
            2 => $contentLengthRangeValue,
        ];
        $conditions[] = $condition;

        $start = [
            0 => 'starts-with',
            1 => '$key',
            2 => ltrim($prefix, '/'),
        ];
        $conditions[] = $start;

        $arr = [
            'expiration' => $expiration,
            'conditions' => $conditions,
        ];
        $policy = json_encode($arr);
        $base64Policy = base64_encode($policy);
        $stringToSign = $base64Policy;
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret, true));

        $response = [];
        $response['accessid'] = $this->accessKeyId;
        $response['host'] = $this->normalizeHost();
        $response['policy'] = $base64Policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64CallbackBody;
        $response['callback-var'] = $callbackVar;
        $response['dir'] = ltrim($prefix, '/');  // 这个参数是设置用户上传文件时指定的前缀。

        return $response;
    }

    public function getTokenConfig(string $path = '/', array $customData = [], array $systemData = [])
    {
        return $this->signatureConfig($path, $this->callBackUrl, $customData, $this->expire, $this->contentLengthRangeValue, $systemData);
    }

    /**
     * gmt.
     *
     * @param $time
     *
     * @return string
     *
     * @throws \Exception
     */
    public function gmt_iso8601($time)
    {
        // fix bug https://connect.console.aliyun.com/connect/detail/162632
        return (new \DateTime('now', new \DateTimeZone('UTC')))->setTimestamp($time)->format('Y-m-d\TH:i:s\Z');
    }
}
