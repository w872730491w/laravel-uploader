<?php

it('local', function () {
    config()->set('filesystems.disks.uploader.type', 'local');
    $storage = app('filesystem')->disk('uploader');
    $res = $storage->getAdapter()->getTokenConfig('image');
    dump(json_encode($res, JSON_UNESCAPED_UNICODE));
    expect($res)->toBeArray()->toHaveKeys([
        'driver',
        'config',
        'config.host',
        'config.prefix',
        'config.max_size',
        'config.mime_types',
        'config.expire_time',
    ]);
});

it('aliyun', function () {
    config()->set('filesystems.disks.uploader.type', 'aliyun');
    $storage = app('filesystem')->disk('uploader');
    $res = $storage->getAdapter()->getTokenConfig('files');
    dump(json_encode($res, JSON_UNESCAPED_UNICODE));

    expect($res)->toBeArray()->toHaveKeys([
        'driver',
        'config',
        'config.host',
        'config.type',
        'config.prefix',
        'config.max_size',
        'config.mime_types',
        'config.expire_time',
        'config.aliyun.accessid',
        'config.aliyun.policy',
        'config.aliyun.signature',
        'config.aliyun.forbid_overwrite',
    ]);
});

it('tencent', function () {
    config()->set('filesystems.disks.uploader.type', 'tencent');
    $storage = app('filesystem')->disk('uploader');
    $res = $storage->getAdapter()->getTokenConfig('files');
    dump(json_encode($res, JSON_UNESCAPED_UNICODE));

    expect($res)->toBeArray()->toHaveKeys([
        'driver',
        'config',
        'config.type',
        'config.prefix',
        'config.max_size',
        'config.mime_types',
        'config.expire_time',
        'config.tencent.bucket',
        'config.tencent.region',
        'config.tencent.tempKeys',
    ]);
});

// it('qiniu', function () {
//     config()->set('filesystems.disks.uploader.type', 'qiniu');
//     $storage = app('filesystem')->disk('uploader');
//     $res = $storage->getAdapter()->getTokenConfig('image');
//     expect($res)->toBeArray()->toHaveKeys([
//         'prefix',
//         'token',
//         'expire_time',
//         'domain',
//         'max_size',
//         'mime_types',
//     ]);
// });
