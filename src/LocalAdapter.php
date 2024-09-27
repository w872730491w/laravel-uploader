<?php

namespace Lanyunit\FileSystem\Uploader;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Local\FallbackMimeTypeDetector;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\Flysystem\Config;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnableToSetVisibility;

class LocalAdapter extends LocalFilesystemAdapter
{
    private PathPrefixer $prefixer;
    private VisibilityConverter $visibility;
    private MimeTypeDetector $mimeTypeDetector;
    private string $rootLocation;

    /**
     * @var bool
     */
    private $rootLocationIsSetup = false;

    public function __construct(
        string $location,
        VisibilityConverter $visibility = null,
        private int $writeFlags = LOCK_EX,
        private int $linkHandling = self::DISALLOW_LINKS,
        MimeTypeDetector $mimeTypeDetector = null,
        bool $lazyRootCreation = false,
        protected $expire_time,
        protected $prefix,
        protected string $callback_url,
    ) {
        parent::__construct($location, $visibility, $writeFlags, $linkHandling);
        $this->prefixer = new PathPrefixer($location, DIRECTORY_SEPARATOR);
        $visibility ??= new PortableVisibilityConverter();
        $this->visibility = $visibility;
        $this->rootLocation = $location;
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FallbackMimeTypeDetector(new FinfoMimeTypeDetector());

        if (!$lazyRootCreation) {
            $this->ensureRootDirectoryExists();
        }
    }

    private function ensureRootDirectoryExists(): void
    {
        if ($this->rootLocationIsSetup) {
            return;
        }

        $this->ensureDirectoryExists($this->rootLocation, $this->visibility->defaultForDirectories());
    }

    /**
     * @param resource|string $contents
     */
    private function writeToFile(string $path, $contents, Config $config): void
    {
        $prefixedLocation = $this->prefixer->prefixPath($path);
        $this->ensureRootDirectoryExists();
        $this->ensureDirectoryExists(
            dirname($prefixedLocation),
            $this->resolveDirectoryVisibility($config->get(Config::OPTION_DIRECTORY_VISIBILITY))
        );
        error_clear_last();

        if (@file_put_contents($prefixedLocation, $contents, $this->writeFlags) === false) {
            throw UnableToWriteFile::atLocation($path, error_get_last()['message'] ?? '');
        }

        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $this->setVisibility($path, (string) $visibility);
        }
    }

    private function listDirectoryRecursively(
        string $path,
        int $mode = \RecursiveIteratorIterator::SELF_FIRST
    ): \Generator {
        if (!is_dir($path)) {
            return;
        }

        yield from new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            $mode
        );
    }

    private function resolveDirectoryVisibility(?string $visibility): int
    {
        return $visibility === null ? $this->visibility->defaultForDirectories() : $this->visibility->forDirectory(
            $visibility
        );
    }

    private function listDirectory(string $location): \Generator
    {
        $iterator = new \DirectoryIterator($location);

        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            yield $item;
        }
    }

    private function setPermissions(string $location, int $visibility): void
    {
        error_clear_last();
        if (!@chmod($location, $visibility)) {
            $extraMessage = error_get_last()['message'] ?? '';
            throw UnableToSetVisibility::atLocation($this->prefixer->stripPrefix($location), $extraMessage);
        }
    }

    /**
     * 获取上传配置
     * @param string $type
     * @return array
     */
    public function getTokenConfig($type = null)
    {
        $allow = Uploader::getAllowType($type);

        $policy = [
            'allowPrefix' => $this->prefix,
            'maxSize' => $allow['max_size'],
            'callbackUrl' => $this->callback_url,
            'expireTime' => time() + $this->expire_time,
            'mimeTypes' => $allow['mimetypes']
        ];

        return [
            'host' => url('api/upload/put'),
            'prefix' => $this->prefix,
            'max_size' => $allow['max_size'],
            'callback_url' => $this->callback_url,
            'auth' => encrypt($policy),
            'expire_time' => $policy['expireTime'],
            'mime_types' => $allow['mimetypes']
        ];
    }
}
