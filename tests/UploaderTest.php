<?php

it('local', function () {
    config()->set('filesystems.disks.uploader.type', 'local');
    $storage = app('filesystem')->disk('uploader');
    $res = $storage->getAdapter()->getTokenConfig('image');
    expect($res)->toBeArray('获取本地配置成功')->toHaveKeys([
        'host',
        'prefix',
        'max_size',
        'callback_url',
        'auth',
        'expire_time',
        'mime_types'
    ], '本地配置验证成功');
});

it('aliyun', function () {
    config()->set('filesystems.disks.uploader.type', 'aliyun');
    $storage = app('filesystem')->disk('uploader');
    $res = $storage->getAdapter()->getTokenConfig('image');
    expect($res)->toBeArray('获取阿里云oss配置成功')->toHaveKeys([
        'accessid',
        'host',
        'policy',
        'signature',
        'expire_time',
        'callback',
        'callback-var',
        'mime_types',
        'max_size',
        'dir',
    ], '阿里云oss配置验证成功');
});

it('tencent', function () {
    config()->set('filesystems.disks.uploader.type', 'tencent');
    $storage = app('filesystem')->disk('uploader');
    $res = $storage->getAdapter()->getTokenConfig('image');
    expect($res)->toBeArray('获取腾讯cos配置成功')->toHaveKeys([
        'callback_url',
        'bucket',
        'region',
        'path',
        'tempKeys',
        'mime_types',
        'max_size',
        'expire_time',
        'auth',
    ], '腾讯cos配置验证成功');
});

it('qiniu', function () {
    config()->set('filesystems.disks.uploader.type', 'qiniu');
    $storage = app('filesystem')->disk('uploader');
    $res = $storage->getAdapter()->getTokenConfig('image');
    expect($res)->toBeArray('获取七牛云配置成功')->toHaveKeys([
        'prefix',
        'token',
        'expire_time',
        'domain',
        'max_size',
        'mime_types',
    ], '七牛云配置验证成功');
});