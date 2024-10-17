<?php

namespace Lanyunit\FileSystem\Uploader\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class Callback extends Controller
{
    /**
     * 回调
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        Storage::purge();
        $storage = Storage::disk();
        $config = $storage->getConfig();

        if ($config['type'] === 'aliyun') {
            $adapter = $storage->getAdapter();
            [$verify, $data] = $adapter->verify();

            if (!$verify) {
                return response()->json($data, 401);
            }

            if ($this->checkMimeType($data['mimeType'], $data['allowMimeType']) === false) {
                try {
                    $adapter->delete(pathinfo($data['filename'], PATHINFO_BASENAME));
                } catch (\Throwable $th) {
                    //throw $th;
                }
                return response()->json([
                    'msg' => '文件类型不符'
                ], 403);
            }

            return response()->json([
                'url' => $adapter->normalizeHost() . $data['filename']
            ]);
        }

        if ($config['type'] === 'qiniu') {
            $adapter = $storage->getAdapter();

            $verify = $adapter->verifyCallback(request()->header('content-type'), request()->header('authorization'), $adapter->normalizeHost($config['callback_url']), request()->getContent());

            if (!$verify) {
                return response()->json(['msg' => 'Unauthorization'], 401);
            }

            $data = request()->post();

            return response()->json([
                'url' => $data['url']
            ]);
        }

        if ($config['type'] === 'tencent') {
            $post = request()->post();

            $policy = decrypt($post['auth']);

            if (!is_array($policy) && $post['sessionToken'] != $policy['token']) {
                return response()->json(['msg' => 'Unauthorization'], 401);
            }

            if ($post['size'] > $policy['maxSize']) {
                return response()->json([
                    'msg' => '文件超出最大上传大小限制'
                ], 413);
            }

            if ($this->checkMimeType($post['mimetype'], $policy['mimeTypes']) === false) {
                return response()->json([
                    'msg' => '文件类型不符'
                ], 403);
            }

            return response()->json([
                'url' => '//' . $post['localtion']
            ]);
        }

        abort(404);
    }

    /**
     * 上传
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        Storage::purge();
        $storage = Storage::disk();
        $config = $storage->getConfig();

        if ($config['type'] === 'local') {
            $data = $request->validate([
                'auth' => 'required',
                'key' => 'required|string',
                'file' => 'required|file',
            ]);

            $policy = decrypt($data['auth']);

            if (!is_array($policy) || !isset($policy['allowPrefix']) || !isset($policy['maxSize']) || !isset($policy['callbackUrl']) || !isset($policy['expireTime']) || !isset($policy['mimeTypes'])) {
                return response()->json(null, 401);
            }

            if (time() > $policy['expireTime']) {
                return response()->json(null, 401);
            }

            if (strpos($policy['allowPrefix'], $data['key']) != 0) {
                return response()->json([
                    'msg' => 'prefix not allow'
                ], 422);
            }

            /**
             * @var UploadedFile $file
             */
            $file = $data['file'];
            $fileSize = $file->getSize();
            $mimeType = $file->getClientMimeType();

            if ($fileSize > $policy['maxSize'] || $fileSize > $file->getMaxFilesize()) {
                return response()->json([
                    'msg' => '文件超出最大上传大小限制'
                ], 413);
            }

            if ($this->checkMimeType($mimeType, $policy['mimeTypes']) === false) {
                return response()->json([
                    'msg' => '文件类型不符'
                ], 403);
            }

            $pathinfo = pathinfo($data['key']);

            $key = $file->storePubliclyAs(
                $pathinfo['dirname'],
                $pathinfo['basename']
            );

            if ($key === false) {
                return response('', 500);
            }

            return response()->json([
                'url' => Storage::disk('public')->url($key)
            ]);
        }

        abort(404);
    }

    /**
     * 检查mimetype
     * @param string $file_mime
     * @param array|string $allow_mime
     * @return bool
     */
    public function checkMimeType(string $file_mime, array|string $allow_mime): bool
    {
        if ($allow_mime === '*') {
            return true;
        }

        if (is_array($allow_mime)) {
            $res = false;
            foreach ($allow_mime as $v) {
                if ($this->checkMimeTypeByString($file_mime, $v)) {
                    $res = true;
                    break;
                }
            }
            return $res;
        }

        return $this->checkMimeTypeByString($file_mime, $allow_mime);
    }

    /**
     * 检查mimetype
     * @param string $file_mime
     * @param string $allow_mime
     * @return bool
     */
    public function checkMimeTypeByString(string $file_mime, string $allow_mime): bool
    {
        if (strpos('/*', $allow_mime) !== false && strpos($file_mime, trim($allow_mime, '/*')) === 0) {
            return true;
        }
        if ($allow_mime === $file_mime) {
            return true;
        }
        return false;
    }
}
