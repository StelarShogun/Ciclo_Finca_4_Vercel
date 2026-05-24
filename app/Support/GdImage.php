<?php

namespace App\Support;

final class GdImage
{
    public static function supportsWebp(): bool
    {
        if (! function_exists('imagecreatefromwebp')) {
            return false;
        }

        $info = gd_info();

        return ! empty($info['WebP Support']);
    }
}
