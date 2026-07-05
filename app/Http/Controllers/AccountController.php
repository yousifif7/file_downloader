<?php

namespace App\Http\Controllers;

use App\Models\PlanUpgradeRequest;
use App\Services\DownloadQuotaService;
use App\Services\PlisioBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(DownloadQuotaService $quotaService, PlisioBillingService $cryptoBilling): View
    {
        $cryptoBilling->expireStaleCryptoRequests();

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
                ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_BANK)
                ->latest()
                ->get(),
            'pendingCryptoRequests' => $user->planUpgradeRequests()
                ->with('plan')
                ->where('status', PlanUpgradeRequest::STATUS_PENDING)
                ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO)
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

    public function cancelCryptoUpgrade(PlanUpgradeRequest $upgradeRequest, PlisioBillingService $cryptoBilling): RedirectResponse
    {
        abort_unless($upgradeRequest->user_id === auth()->id(), 403);

        $cryptoBilling->cancelRequest($upgradeRequest, 'Cancelled by customer.');

        return back()->with('status', 'Crypto checkout cancelled. You can start a new payment anytime.');
    }
}
