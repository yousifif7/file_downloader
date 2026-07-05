<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\PlanUpgradeRequest;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\PlisioBillingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(PlisioBillingService $cryptoBilling): View
    {
        $cryptoBilling->expireStaleCryptoRequests();

        $pendingUpgradeCount = PlanUpgradeRequest::query()
            ->where('status', PlanUpgradeRequest::STATUS_PENDING)
            ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_BANK)
            ->count();

        $awaitingStaffTicketCount = SupportTicket::query()
            ->where('status', SupportTicket::STATUS_OPEN)
            ->where('awaiting_staff', true)
            ->count();

        return view('admin.dashboard', [
            'downloadsToday' => Download::query()->whereDate('created_at', today())->count(),
            'downloadsWeek' => Download::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'downloadsTotal' => Download::query()->count(),
            'failedDownloads' => Download::query()->where('status', Download::STATUS_FAILED)->count(),
            'usersCount' => User::query()->count(),
            'pendingUpgradeCount' => $pendingUpgradeCount,
            'awaitingStaffTicketCount' => $awaitingStaffTicketCount,
            'recentPendingUpgrades' => PlanUpgradeRequest::query()
                ->with(['user', 'plan'])
                ->where('status', PlanUpgradeRequest::STATUS_PENDING)
                ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_BANK)
                ->latest()
                ->limit(5)
                ->get(),
            'recentAwaitingTickets' => SupportTicket::query()
                ->with('user')
                ->where('status', SupportTicket::STATUS_OPEN)
                ->where('awaiting_staff', true)
                ->latest('last_message_at')
                ->limit(5)
                ->get(),
            'recentDownloads' => Download::query()
                ->with(['user', 'platform'])
                ->latest()
                ->limit(10)
                ->get(),
            'topPlatforms' => Download::query()
                ->selectRaw('platform_id, count(*) as total')
                ->whereNotNull('platform_id')
                ->groupBy('platform_id')
                ->with('platform')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
        ]);
    }
}
