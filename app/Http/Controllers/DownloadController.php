<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyzeUrlRequest;
use App\Http\Requests\StoreDownloadRequest;
use App\Jobs\ProcessDownloadJob;
use App\Models\Download;
use App\Models\Platform;
use App\Services\ExtractorRegistry;
use App\Services\PlanCatalogService;
use App\Services\PlanPlatformAccessService;
use App\Services\UrlDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function home(PlanCatalogService $catalog): View
    {
        return view('landing', [
            'platforms' => Platform::query()->orderBy('name')->get(),
            'plans' => $catalog->activePlans(),
            'freePlan' => $catalog->freePlan(),
            'catalog' => $catalog,
        ]);
    }

    public function analyze(
        AnalyzeUrlRequest $request,
        ExtractorRegistry $registry,
        UrlDetector $detector,
        PlanPlatformAccessService $platformAccess,
    ): JsonResponse
    {
        $key = $request->user()
            ? 'analyze:'.$request->user()->id
            : 'analyze:ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $request->user() ? 20 : 10)) {
            return response()->json(['message' => 'Too many requests. Please wait a moment.'], 429);
        }

        RateLimiter::hit($key, 60);

        $url = (string) $request->validated('url');
        $slug = $detector->detect($url);

        if ($request->user() !== null && ! $platformAccess->userCanAccessPlatform($request->user(), $slug)) {
            return response()->json([
                'message' => $platformAccess->denialMessage($request->user(), (string) $slug),
                'code' => 'platform_not_included',
            ], 403);
        }

        try {
            $extractor = $registry->resolveForUrl($url);
            $metadata = $extractor->metadata($url);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'platform_slug' => $slug,
            'title' => $metadata['title'],
            'thumbnail' => $metadata['thumbnail'],
            'formats' => $metadata['formats'],
        ]);
    }

    public function store(StoreDownloadRequest $request, PlanPlatformAccessService $platformAccess): RedirectResponse
    {
        $key = 'download:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['url' => 'Too many download requests. Please wait a moment.']);
        }

        RateLimiter::hit($key, 60);

        $slug = $request->input('platform_slug') ?: app(UrlDetector::class)->detect((string) $request->validated('url'));

        if (! $platformAccess->userCanAccessPlatform($request->user(), $slug)) {
            return back()->withErrors([
                'url' => $platformAccess->denialMessage($request->user(), (string) $slug),
            ]);
        }

        $platform = Platform::query()->where('slug', $slug)->first();

        $download = Download::query()->create([
            'user_id' => $request->user()->id,
            'platform_id' => $platform?->id,
            'source_url' => $request->validated('url'),
            'media_title' => $request->input('title'),
            'format' => $request->validated('format'),
            'status' => Download::STATUS_PENDING,
            'ip_address' => $request->ip(),
        ]);

        ProcessDownloadJob::dispatch($download);

        return redirect()->route('downloads.show', $download);
    }

    public function show(Download $download): View
    {
        $this->authorizeDownload($download);

        $download->load('platform');

        $downloadUrl = null;
        if ($download->isReady() && Storage::disk('local')->exists($download->file_path)) {
            $downloadUrl = route('downloads.file', $download);
        }

        return view('downloads.show', [
            'download' => $download,
            'downloadUrl' => $downloadUrl,
        ]);
    }

    public function file(Request $request, Download $download): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            if (! auth()->check()) {
                abort(403);
            }

            $this->authorizeDownload($download);
        } else {
            $this->authorizeDownload($download);
        }

        if (! $download->isReady() || ! $download->file_path) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($download->file_path)) {
            abort(404);
        }

        $filename = $this->safeDownloadFilename($download);

        return Storage::disk('local')->download($download->file_path, $filename);
    }

    private function safeDownloadFilename(Download $download): string
    {
        $title = trim(str_replace(['/', '\\'], '-', (string) ($download->media_title ?: 'download')));

        if ($title === '') {
            $title = 'download';
        }

        $extension = pathinfo((string) $download->file_path, PATHINFO_EXTENSION);

        return $extension !== '' ? "{$title}.{$extension}" : $title;
    }

    private function authorizeDownload(Download $download): void
    {
        if ($download->user_id !== auth()->id() && ! auth()->user()?->isAdmin()) {
            abort(403);
        }
    }
}
