<?php

namespace App\Services\Shared\Filesystem;

use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;

final class VercelBlobAdapter implements FilesystemAdapter
{
    private const API_BASE_URL = 'https://blob.vercel-storage.com';

    public function __construct(
        private readonly string $token,
        private readonly string $publicUrl,
        private readonly string $prefix = '',
    ) {}

    public function fileExists(string $path): bool
    {
        try {
            return Http::withToken($this->token)->head($this->url($path))->successful();
        } catch (\Throwable $e) {
            throw UnableToCheckFileExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            foreach ($this->listContents($path, false) as $item) {
                return $item instanceof DirectoryAttributes || $item instanceof FileAttributes;
            }

            return false;
        } catch (\Throwable $e) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $e);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->put($path, $contents);
    }

    /**
     * @param  resource  $contents
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $data = stream_get_contents($contents);

        if ($data === false) {
            throw UnableToWriteFile::atLocation($path, 'Could not read stream.');
        }

        $this->put($path, $data);
    }

    public function read(string $path): string
    {
        try {
            $response = Http::withToken($this->token)->get($this->url($path));
        } catch (\Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }

        if (! $response->successful()) {
            throw UnableToReadFile::fromLocation($path, $response->body());
        }

        return $response->body();
    }

    /**
     * @return resource
     */
    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path, 'Could not open temp stream.');
        }

        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $response = Http::withToken($this->token)
                ->delete(self::API_BASE_URL, ['urls' => [$this->url($path)]]);
        } catch (\Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }

        if (! $response->successful()) {
            throw UnableToDeleteFile::atLocation($path, $response->body());
        }
    }

    public function deleteDirectory(string $path): void
    {
        foreach ($this->listContents($path, true) as $item) {
            if ($item instanceof FileAttributes) {
                $this->delete($item->path());
            }
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        if ($path === '') {
            return;
        }

        throw UnableToCreateDirectory::atLocation($path, 'Vercel Blob does not create physical directories.');
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Vercel Blob visibility is configured at store level.');
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, 'public');
    }

    public function mimeType(string $path): FileAttributes
    {
        $response = Http::withToken($this->token)->head($this->url($path));

        if (! $response->successful()) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return new FileAttributes($path, null, null, null, $response->header('content-type'));
    }

    public function lastModified(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        $response = Http::withToken($this->token)->head($this->url($path));

        if (! $response->successful()) {
            throw UnableToRetrieveMetadata::fileSize($path);
        }

        return new FileAttributes($path, (int) $response->header('content-length', 0));
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = trim($this->applyPrefix($path), '/');
        $cursor = null;

        do {
            $query = array_filter([
                'prefix' => $prefix === '' ? null : $prefix.'/',
                'cursor' => $cursor,
                'limit' => 1000,
            ]);

            $response = Http::withToken($this->token)->get(self::API_BASE_URL, $query);

            if (! $response->successful()) {
                return;
            }

            $payload = $response->json();

            foreach (($payload['blobs'] ?? []) as $blob) {
                $pathname = (string) ($blob['pathname'] ?? '');
                $relative = $this->stripPrefix($pathname);

                if ($relative === '') {
                    continue;
                }

                if (! $deep && str_contains(trim(substr($relative, strlen(trim($path, '/'))), '/'), '/')) {
                    $directory = strtok($relative, '/');
                    if ($directory !== false) {
                        yield new DirectoryAttributes($directory);
                    }

                    continue;
                }

                yield new FileAttributes(
                    $relative,
                    isset($blob['size']) ? (int) $blob['size'] : null,
                    'public',
                    isset($blob['uploadedAt']) ? strtotime((string) $blob['uploadedAt']) : null,
                    null,
                );
            }

            $cursor = $payload['cursor'] ?? null;
        } while ($cursor !== null);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (FilesystemException $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->write($destination, $this->read($source), $config);
        } catch (FilesystemException $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function publicUrl(string $path, Config $config): string
    {
        return $this->url($path);
    }

    public function getUrl(string $path): string
    {
        return $this->url($path);
    }

    private function put(string $path, string $contents): void
    {
        try {
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'x-add-random-suffix' => '0',
                    'x-allow-overwrite' => '1',
                    'content-type' => 'application/octet-stream',
                ])
                ->withBody($contents, 'application/octet-stream')
                ->put(self::API_BASE_URL.'/'.$this->applyPrefix($path));
        } catch (\Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }

        if (! $response->successful()) {
            throw UnableToWriteFile::atLocation($path, $response->body());
        }
    }

    private function url(string $path): string
    {
        if (str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim($this->publicUrl, '/').'/'.$this->applyPrefix($path);
    }

    private function applyPrefix(string $path): string
    {
        return ltrim(trim($this->prefix, '/').'/'.ltrim($path, '/'), '/');
    }

    private function stripPrefix(string $path): string
    {
        $prefix = trim($this->prefix, '/');

        if ($prefix === '') {
            return ltrim($path, '/');
        }

        return ltrim(preg_replace('#^'.preg_quote($prefix, '#').'/?#', '', $path) ?? $path, '/');
    }
}
