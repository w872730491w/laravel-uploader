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
