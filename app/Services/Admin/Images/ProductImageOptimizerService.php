<?php

namespace App\Services\Admin\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class ProductImageOptimizerService
{
    public function supportsWebp(): bool
    {
        return function_exists('imagewebp');
    }

    /**
     * Rebuild image via GD to strip metadata and reject polyglot payloads.
     *
     * @return string Absolute path to sanitized file (may replace extension with .webp or .jpg)
     */
    public function sanitizePath(string $sourcePath): string
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException('Image file is not readable.');
        }

        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new RuntimeException('Invalid image file.');
        }

        $mime = $imageInfo['mime'] ?? '';
        $resource = match ($mime) {
            'image/jpeg' => \function_exists('imagecreatefromjpeg') ? @\imagecreatefromjpeg($sourcePath) : false,
            'image/png' => @\imagecreatefrompng($sourcePath),
            'image/gif' => \function_exists('imagecreatefromgif') ? @\imagecreatefromgif($sourcePath) : false,
            'image/webp' => \function_exists('imagecreatefromwebp') ? @\imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($resource === false) {
            throw new RuntimeException('Unsupported or corrupt image format.');
        }

        if ($mime === 'image/png') {
            \imagealphablending($resource, false);
            \imagesavealpha($resource, true);
        }

        $directory = dirname($sourcePath);
        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $targetPath = $directory.DIRECTORY_SEPARATOR.$baseName.'_sanitized';

        if ($this->supportsWebp()) {
            $targetPath .= '.webp';
            $saved = \imagewebp($resource, $targetPath, 82);
        } elseif (\function_exists('imagejpeg')) {
            $targetPath .= '.jpg';
            $saved = \imagejpeg($resource, $targetPath, 85);
        } else {
            $targetPath .= '.png';
            $saved = \imagepng($resource, $targetPath, 6);
        }

        \imagedestroy($resource);

        if (! $saved || ! is_file($targetPath)) {
            throw new RuntimeException('Failed to sanitize image.');
        }

        @unlink($sourcePath);

        return $targetPath;
    }

    public function sanitizeUploadedFile(UploadedFile $file): string
    {
        $tempRoot = config('vercel.enabled')
            ? sys_get_temp_dir().DIRECTORY_SEPARATOR.'cf4-temp'
            : storage_path('app/temp');
        $tempDir = $tempRoot.DIRECTORY_SEPARATOR.now()->format('Y-m-d');
        File::ensureDirectoryExists($tempDir);

        $tempName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $tempPath = $tempDir.DIRECTORY_SEPARATOR.$tempName;
        $file->move($tempDir, $tempName);

        return $this->sanitizePath($tempPath);
    }

    public function moveToPermanent(string $tempPath, string $destinationDirectory, string $filename): string
    {
        File::ensureDirectoryExists($destinationDirectory);
        $destination = rtrim($destinationDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;

        if (! @rename($tempPath, $destination)) {
            File::copy($tempPath, $destination);
            @unlink($tempPath);
        }

        return $destination;
    }
}
