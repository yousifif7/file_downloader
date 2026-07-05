<?php

namespace App\Services\Extractors;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DirectFileExtractor implements ExtractorInterface
{
    public function supports(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function metadata(string $url): array
    {
        $path = parse_url($url, PHP_URL_PATH);
        $filename = is_string($path) ? basename($path) : 'file';
        $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin';

        return [
            'title' => $filename !== '' ? $filename : 'Direct file',
            'thumbnail' => null,
            'formats' => [
                [
                    'id' => 'direct',
                    'label' => 'Original file ('.strtoupper($ext).')',
                    'ext' => $ext,
                ],
            ],
        ];
    }

    public function download(string $url, string $formatId): array
    {
        $maxBytes = Setting::getInt('max_file_size_mb', 500) * 1024 * 1024;
        $filename = Str::uuid().'.bin';
        $relativePath = 'downloads/'.$filename;
        $absolutePath = Storage::disk('local')->path($relativePath);

        Storage::disk('local')->makeDirectory('downloads');

        $response = Http::timeout(120)
            ->withOptions(['sink' => $absolutePath])
            ->get($url);

        if (! $response->successful()) {
            @unlink($absolutePath);
            throw new RuntimeException('Failed to download the file.');
        }

        $size = filesize($absolutePath) ?: 0;

        if ($size > $maxBytes) {
            @unlink($absolutePath);
            throw new RuntimeException('File exceeds the maximum allowed size.');
        }

        $path = parse_url($url, PHP_URL_PATH);
        $basename = is_string($path) ? basename($path) : 'download.bin';
        $ext = pathinfo($basename, PATHINFO_EXTENSION);

        if ($ext) {
            $newRelative = 'downloads/'.Str::uuid().'.'.$ext;
            Storage::disk('local')->move($relativePath, $newRelative);
            $relativePath = $newRelative;
        }

        return [
            'path' => $relativePath,
            'size' => $size,
        ];
    }
}
