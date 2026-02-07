<?php

namespace Lanyunit\FileSystem\Uploader;

use Iidestiny\Flysystem\Oss\OssAdapter;
use League\Flysystem\PathPrefixer;

class AliyunOssAdapter extends OssAdapter
{
    protected $callBackUrl;

    protected $expire;

    protected $contentLengthRangeValue;

    protected $bucket;

    protected $forbidOverwrite;

    /**
     * @throws \OSS\Core\OssException
     */
    public function __construct($accessKeyId, $accessKeySecret, $endpoint, $bucket, bool $isCName = false, string $prefix = '', $callBackUrl = '', int $expire = 30, array $buckets = [], bool $forbidOverwrite = false, ...$params)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->endpoint = $endpoint;
        $this->bucket = $bucket;
        $this->isCName = $isCName;
        $this->prefixer = new PathPrefixer($prefix, '/');
        $this->buckets = $buckets;
        $this->callBackUrl = $callBackUrl;
        $this->expire = $expire;
        $this->params = $params;
        $this->forbidOverwrite = $forbidOverwrite;
        $this->initClient();
        $this->checkEndpoint();
    }

    /**
     * getDir
     *
     * @return string
     */
    public function getDir(string $path = '/')
    {
        return ltrim($this->prefixer->prefixPath($path . '/'), '/');
    }

    /**
     * normalize Host.
     */
    public function normalizeHost(): string
    {
        if ($this->isCName) {
            $domain = $this->endpoint;
        } else {
            $domain = $this->bucket . '.' . $this->endpoint;
        }

        if ($this->useSSL) {
            $domain = "https://{$domain}";
        } else {
            $domain = "http://{$domain}";
        }

        return rtrim($domain, '/') . '/';
    }

    /**
     * OSS直传配置
     *
     * @param  string|null  $type
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getTokenConfig($type = null, $path = '/', array $customData = [], array $systemData = [])
    {
        $allow = Uploader::getAllowType($type);

        $prefix = $this->getDir($path);

        // 系统参数
        $system = [];
        if (empty($systemData)) {
            $system = self::SYSTEM_FIELD;
        } else {
            foreach ($systemData as $key => $value) {
                if (! in_array($value, self::SYSTEM_FIELD)) {
                    throw new \InvalidArgumentException("Invalid oss system filed: $value");
                }
                $system[$key] = $value;
            }
        }

        // 自定义参数
        $callbackVar = [];
        $data = [
            'mimeType' => '${mimeType}',
            'allowMimeType' => $allow['mimetypes'],
            'type' => $type,
        ];
        if (! empty($customData)) {
            foreach ($customData as $key => $value) {
                $callbackVar['x:' . $key] = $value;
                $data[$key] = '${x:' . $key . '}';
            }
        }

        if (! empty($this->callBackUrl)) {
            $callbackParam = [
                'callbackUrl' => $this->callBackUrl,
                'callbackBody' => urldecode(http_build_query(array_merge($system, $data))),
                'callbackBodyType' => 'application/x-www-form-urlencoded',
            ];
            $callbackString = json_encode($callbackParam);
            $base64CallbackBody = base64_encode($callbackString);
        }


        $now = time();
        $end = $now + $this->expire;
        $expiration = $this->gmt_iso8601($end);

        // 最大文件大小.用户可以自己设置
        $condition = [
            0 => 'content-length-range',
            1 => 0,
            2 => $allow['max_size'],
        ];
        $conditions[] = $condition;

        // 允许上传的前缀
        $start = [
            0 => 'starts-with',
            1 => '$key',
            2 => $prefix,
        ];
        $conditions[] = $start;

        // 允许上传的文件类型 mimeType
        if (is_array($allow['mimetypes'])) {
            $allowTypes = [];
            foreach ($allow['mimetypes'] as $v) {
                if (strpos('/*', $v) === false) {
                    $allowTypes[] = $v;
                }
            }
            $contentType = [
                0 => 'in',
                1 => '$content-type',
                2 => $allowTypes,
            ];
            $conditions[] = $contentType;
        }

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
        $response['policy'] = $base64Policy;
        $response['signature'] = $signature;
        if (isset($base64CallbackBody)) {
            $response['callback'] = $base64CallbackBody;
            $response['callback-var'] = $callbackVar;
        }
        $response['forbid_overwrite'] = $this->forbidOverwrite;

        return [
            'driver' => 'aliyun',
            'config' => [
                'host' => $this->normalizeHost(),
                'type' => $type,
                'prefix' => $prefix,
                'max_size' => $allow['max_size'],
                'mime_types' => $allow['mimetypes'],
                'expire_time' => $end,
                'aliyun' => $response,
            ]
        ];
    }

    /**
     * gmt.
     *
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

    /**
     * 验签.
     */
    public function verify($authorizationBase64 = '', $pubKeyUrlBase64 = '', $path = '', $body = ''): array
    {
        // oss 前面header、公钥 header
        $request = request();
        $authorizationBase64 = $request->header('authorization', '');
        $pubKeyUrlBase64 = $request->header('x-oss-pub-key-url', '');

        // 验证失败
        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '') {
            return [false, ['CallbackFailed' => 'authorization or pubKeyUrl is null']];
        }

        // 获取OSS的签名
        $authorization = base64_decode($authorizationBase64);
        // 获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        // 请求验证
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);

        if ($pubKey == '') {
            return [false, ['CallbackFailed' => 'curl is fail']];
        }

        // 获取回调 body
        $body = $request->getContent();
        // 拼接待签名字符串
        $path = $request->getRequestUri();
        $pos = strpos($path, '?');
        if ($pos === false) {
            $authStr = urldecode($path) . "\n" . $body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)) . substr($path, $pos, strlen($path) - $pos) . "\n" . $body;
        }
        // 验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);

        if ($ok !== 1) {
            curl_close($ch);

            return [false, ['CallbackFailed' => 'verify is fail, Illegal data']];
        }

        parse_str($body, $data);

        curl_close($ch);

        return [true, $data];
    }
}
