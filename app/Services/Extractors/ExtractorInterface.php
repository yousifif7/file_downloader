<?php

namespace App\Services\Extractors;

interface ExtractorInterface
{
    public function supports(string $url): bool;

    /**
     * @return array{title: string, thumbnail: ?string, formats: array<int, array{id: string, label: string, ext: string}>}
     */
    public function metadata(string $url): array;

    /**
     * @return array{path: string, size: int}
     */
    public function download(string $url, string $formatId): array;
}
