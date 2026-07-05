<?php

namespace App\Services;

use App\Models\Platform;
use App\Services\Extractors\ExtractorInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class ExtractorRegistry
{
    public function __construct(
        private readonly Container $container,
        private readonly UrlDetector $urlDetector,
    ) {}

    public function resolveForUrl(string $url): ExtractorInterface
    {
        $slug = $this->urlDetector->detect($url);

        if ($slug === null) {
            throw new InvalidArgumentException('Could not detect a platform for this URL.');
        }

        return $this->resolveBySlug($slug);
    }

    public function resolveBySlug(string $slug): ExtractorInterface
    {
        $platform = Platform::query()
            ->where('slug', $slug)
            ->where('is_enabled', true)
            ->first();

        if ($platform === null) {
            throw new InvalidArgumentException("Platform [{$slug}] is not available.");
        }

        $extractor = $this->container->make($platform->extractor_class);

        if (! $extractor instanceof ExtractorInterface) {
            throw new InvalidArgumentException("Extractor for [{$slug}] is invalid.");
        }

        return $extractor;
    }
}
