<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanUpgradeRequest;
use App\Services\ManualBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanUpgradeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $requests = PlanUpgradeRequest::query()
            ->with(['user', 'plan', 'reviewer'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search').'%';
                $query->where(function ($inner) use ($search): void {
                    $inner->where('payment_reference', 'like', $search)
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $pendingCount = PlanUpgradeRequest::query()
            ->where('status', PlanUpgradeRequest::STATUS_PENDING)
            ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_BANK)
            ->count();

        return view('admin.upgrade-requests.index', [
            'requests' => $requests,
            'status' => $status,
            'search' => $request->string('search')->toString(),
            'pendingCount' => $pendingCount,
        ]);
    }

    public function approve(PlanUpgradeRequest $upgradeRequest, Request $request, ManualBillingService $billing): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $billing->approve($upgradeRequest, $request->user(), $validated['admin_note'] ?? null);

        return back()->with('status', 'Upgrade approved and plan activated for '.$upgradeRequest->user?->email.'.');
    }

    public function reject(PlanUpgradeRequest $upgradeRequest, Request $request, ManualBillingService $billing): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $billing->reject($upgradeRequest, $request->user(), $validated['admin_note'] ?? null);

        return back()->with('status', 'Upgrade request rejected.');
    }
}
