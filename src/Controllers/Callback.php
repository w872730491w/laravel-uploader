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
                return response()->json($data, 401);
            }

            $data['url'] = $adapter->normalizeHost() . ltrim($adapter->getDir()) . '/' . $data['filename'];
            return response()->json($data);
        }

        abort(404);
    }
}
