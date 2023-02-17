<?php

namespace Lanyunit\FileSystem\Uploader\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Callback extends Controller
{
    public function index()
    {
        $storage = Storage::disk();
        $config = $storage->getConfig();

        if ($config['type'] === 'aliyun') {
            $adapter = $storage->getAdapter();
            list($verify, $data) = $adapter->verify();
            
            Log::build([
                'path' => storage_path('logs/upload/info.log'),
            ])->error('上传回调', [
                'auth' => $_SERVER['HTTP_AUTHORIZATION'],
                'pub_key' => $_SERVER['HTTP_X_OSS_PUB_KEY_URL'],
            ]);
            
            if (!$verify) {
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/upload/error.log'),
                ])->error('上传回调失败', $data);
            }

            return response()->json($data);
        }

        abort(404);
    }
}
