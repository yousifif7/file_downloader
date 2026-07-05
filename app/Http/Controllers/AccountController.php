<?php

namespace App\Http\Controllers;

use App\Models\PlanUpgradeRequest;
use App\Services\DownloadQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(DownloadQuotaService $quotaService): View
    {
        $user = auth()->user()->load('plan');

        $upgradeNotifications = $user->planUpgradeRequests()
            ->with('plan')
            ->whereIn('status', [PlanUpgradeRequest::STATUS_APPROVED, PlanUpgradeRequest::STATUS_REJECTED])
            ->whereNull('user_dismissed_at')
            ->latest('reviewed_at')
            ->limit(5)
            ->get();

        return view('account.index', [
            'user' => $user,
            'remaining' => $quotaService->remaining($user),
            'monthlyLimit' => $quotaService->monthlyLimitFor($user),
            'pendingUpgradeRequests' => $user->planUpgradeRequests()
                ->with('plan')
                ->where('status', PlanUpgradeRequest::STATUS_PENDING)
                ->latest()
                ->get(),
            'upgradeNotifications' => $upgradeNotifications,
            // Legacy keys for older cached account views on deployed servers.
            'recentlyApprovedUpgradeRequests' => $upgradeNotifications
                ->where('status', PlanUpgradeRequest::STATUS_APPROVED)
                ->values(),
            'rejectedUpgradeRequests' => $upgradeNotifications
                ->where('status', PlanUpgradeRequest::STATUS_REJECTED)
                ->values(),
            'downloads' => $user->downloads()
                ->with('platform')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function dismissUpgradeRequest(PlanUpgradeRequest $upgradeRequest): RedirectResponse
    {
        abort_unless($upgradeRequest->user_id === auth()->id(), 403);

        $upgradeRequest->dismissForUser();

        return back();
    }
}
