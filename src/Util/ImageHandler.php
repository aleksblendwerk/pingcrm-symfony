<?php

declare(strict_types=1);

namespace App\Util;

use Gumlet\ImageResize;

class ImageHandler
{
    public static function createResizedImageFile(string $path, int $width, int $height): void
    {
        $image = new ImageResize($path);

        $image->resize($width, $height);
        $image->save(self::resolvePathToResizedImage($path, $width, $height));
    }

    public static function resolvePathToResizedImage(string $path, int $width, int $height): string
    {
        $pathInfo = pathinfo($path);

        if (!array_key_exists('extension', $pathInfo)) {
            throw new \RuntimeException(sprintf('pathinfo() did not return an extension for path "%s".', $path));
        }

        return sprintf(
            '%s/%s_%dx%d.%s',
            $pathInfo['dirname'],
            $pathInfo['filename'],
            $width,
            $height,
            $pathInfo['extension']
        );
    }
}
