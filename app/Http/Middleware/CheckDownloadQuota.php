<?php

namespace App\Http\Middleware;

use App\Services\DownloadQuotaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckDownloadQuota
{
    public function __construct(
        private readonly DownloadQuotaService $quotaService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            try {
                $this->quotaService->ensureQuotaAvailable($user);
            } catch (\RuntimeException $exception) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $exception->getMessage(),
                        'code' => 'quota_exceeded',
                    ], 429);
                }

                return $this->redirectWithQuotaNotice($request, $exception->getMessage());
            }
        }

        return $next($request);
    }

    private function redirectWithQuotaNotice(Request $request, string $message): RedirectResponse
    {
        $redirect = redirect()->to(url()->previous().'#download');

        return $redirect
            ->withInput($request->except(['_token']))
            ->with('quota_notice', $message);
    }
}
