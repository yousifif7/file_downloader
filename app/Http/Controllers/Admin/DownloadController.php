<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDownloadJob;
use App\Models\Download;
use App\Models\Platform;
use App\Services\DownloadFailureClassifier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DownloadController extends Controller
{
    public function index(Request $request, DownloadFailureClassifier $failureClassifier): View
    {
        $status = $request->string('status')->toString();
        $platformId = $request->integer('platform_id');
        $failureType = $request->string('failure_type')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $baseQuery = Download::query()
            ->with(['user', 'platform'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($platformId > 0, fn ($query) => $query->where('platform_id', $platformId))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where(function ($inner) use ($search): void {
                    $inner->where('source_url', 'like', '%'.$search.'%')
                        ->orWhere('media_title', 'like', '%'.$search.'%');
                });
            });

        if ($dateFrom !== '') {
            $baseQuery->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $baseQuery->whereDate('created_at', '<=', $dateTo);
        }

        if ($failureType !== '') {
            $this->applyFailureTypeFilter($baseQuery, $failureType, $failureClassifier);
        }

        $downloads = (clone $baseQuery)
            ->latest()
            ->paginate(25);

        $downloads->appends($request->query());

        $downloadFailureLabels = collect($downloads->items())
            ->mapWithKeys(fn (Download $download): array => [
                $download->id => $download->status === Download::STATUS_FAILED
                    ? $failureClassifier->label($download->error_message)
                    : '—',
            ]);

        $summaryQuery = clone $baseQuery;
        $summary = [
            'matching' => (clone $summaryQuery)->count(),
            'today' => (clone $summaryQuery)->whereDate('created_at', today())->count(),
            'failed' => (clone $summaryQuery)->where('status', Download::STATUS_FAILED)->count(),
            'pending' => (clone $summaryQuery)->where('status', Download::STATUS_PENDING)->count(),
            'processing' => (clone $summaryQuery)->where('status', Download::STATUS_PROCESSING)->count(),
            'completed' => (clone $summaryQuery)->where('status', Download::STATUS_COMPLETED)->count(),
        ];
        $summary['failureRate'] = $summary['matching'] > 0
            ? (int) round(($summary['failed'] / $summary['matching']) * 100)
            : 0;

        $failedDownloads = (clone $baseQuery)
            ->where('status', Download::STATUS_FAILED)
            ->get(['error_message']);

        $failureBreakdown = collect($failureClassifier->options())
            ->map(function (string $label, string $type) use ($failedDownloads, $failureClassifier): array {
                $count = $failedDownloads->filter(
                    fn (Download $download): bool => $failureClassifier->classify($download->error_message) === $type
                )->count();

                return [
                    'type' => $type,
                    'label' => $label,
                    'count' => $count,
                ];
            })
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values();

        $platformBreakdown = (clone $baseQuery)
            ->selectRaw("
                COALESCE(platforms.name, 'Unknown') as platform_name,
                count(*) as total,
                sum(case when downloads.status = ? then 1 else 0 end) as failed
            ", [Download::STATUS_FAILED])
            ->leftJoin('platforms', 'platforms.id', '=', 'downloads.platform_id')
            ->groupBy('platforms.name')
            ->orderByDesc('failed')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return view('admin.downloads.index', [
            'downloads' => $downloads,
            'status' => $status,
            'search' => $request->string('search')->toString(),
            'platforms' => Platform::query()->orderBy('name')->get(),
            'platformId' => $platformId > 0 ? $platformId : '',
            'failureType' => $failureType,
            'failureTypes' => $failureClassifier->options(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'summary' => $summary,
            'failureBreakdown' => $failureBreakdown,
            'platformBreakdown' => $platformBreakdown,
            'downloadFailureLabels' => $downloadFailureLabels,
        ]);
    }

    public function show(Download $download, DownloadFailureClassifier $failureClassifier): View
    {
        $download->load(['user', 'platform']);

        return view('admin.downloads.show', [
            'download' => $download,
            'failureType' => $failureClassifier->classify($download->error_message),
            'failureLabel' => $failureClassifier->label($download->error_message),
        ]);
    }

    public function retry(Download $download): \Illuminate\Http\RedirectResponse
    {
        if (! in_array($download->status, [Download::STATUS_FAILED, Download::STATUS_EXPIRED], true)) {
            return back()->withErrors(['download' => 'Only failed or expired downloads can be retried.']);
        }

        if ($download->file_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($download->file_path);
        }

        $download->update([
            'status' => Download::STATUS_PENDING,
            'file_path' => null,
            'file_size_bytes' => null,
            'completed_at' => null,
            'expires_at' => null,
            'error_message' => null,
        ]);

        ProcessDownloadJob::dispatch($download);

        return back()->with('status', 'Download queued for retry.');
    }

    private function applyFailureTypeFilter(
        \Illuminate\Database\Eloquent\Builder $query,
        string $failureType,
        DownloadFailureClassifier $failureClassifier,
    ): void {
        $query->where('status', Download::STATUS_FAILED);

        if ($failureType === 'unknown') {
            foreach ($failureClassifier->options() as $type => $label) {
                if ($type === 'unknown') {
                    continue;
                }

                $query->where(function ($inner) use ($failureClassifier, $type): void {
                    foreach ($failureClassifier->patternsFor($type) as $pattern) {
                        $inner->whereRaw('LOWER(error_message) not like ?', ['%'.$pattern.'%']);
                    }
                });
            }

            return;
        }

        $patterns = $failureClassifier->patternsFor($failureType);

        if ($patterns === []) {
            return;
        }

        $query->where(function ($inner) use ($patterns): void {
            foreach ($patterns as $pattern) {
                $inner->orWhereRaw('LOWER(error_message) like ?', ['%'.$pattern.'%']);
            }
        });
    }
}
