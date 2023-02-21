<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 允许上传的类型
    |--------------------------------------------------------------------------
    |
    | mimetype参考 https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
    |
    */
    'allow' => [
        'audio' => [
            'mime' => ['audio/mpeg'],
            'max_size' => 30
        ],
        'video' => [
            'mime' => ['video/mp4', 'video/quicktime', 'video/mpeg', 'video/avi'],
            'max_size' => 30
        ],
        'files' => [
            'mime' => '*',
            'max_size' => 30
        ],
        'image' => [
            'mime' => ['image/jpeg', 'image/png', 'image/gif'],
            'max_size' => 30
        ],
    ]
];
