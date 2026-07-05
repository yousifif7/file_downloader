<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanUpgradeRequest;
use App\Services\ManualBillingService;
use App\Services\PlanCatalogService;
use App\Services\PlisioBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpgradeController extends Controller
{
    public function index(PlanCatalogService $catalog, PlisioBillingService $cryptoBilling): View
    {
        $cryptoBilling->expireStaleCryptoRequests();

        $user = auth()->user()->load('plan');

        $paidPlans = $catalog->activePlans()->filter(
            fn (Plan $plan) => $plan->slug !== 'free' && $plan->price_cents > 0
        );

        $pendingRequests = PlanUpgradeRequest::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->where('status', PlanUpgradeRequest::STATUS_PENDING)
            ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_BANK)
            ->latest()
            ->get();

        $pendingCryptoRequests = PlanUpgradeRequest::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->where('status', PlanUpgradeRequest::STATUS_PENDING)
            ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO)
            ->latest()
            ->get();

        $rejectedRequests = PlanUpgradeRequest::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->where('status', PlanUpgradeRequest::STATUS_REJECTED)
            ->whereNull('user_dismissed_at')
            ->latest('reviewed_at')
            ->limit(3)
            ->get();

        return view('upgrade.index', [
            'user' => $user,
            'paidPlans' => $paidPlans,
            'allPlans' => $catalog->activePlans(),
            'pendingRequests' => $pendingRequests,
            'pendingCryptoRequests' => $pendingCryptoRequests,
            'rejectedRequests' => $rejectedRequests,
            'catalog' => $catalog,
            'cryptoAvailable' => app(PlisioBillingService::class)->isAvailable(),
        ]);
    }

    public function show(Plan $plan, ManualBillingService $billing, PlanCatalogService $catalog, PlisioBillingService $cryptoBilling): View
    {
        $cryptoBilling->expireStaleCryptoRequests();

        $billing->assertUpgradeablePlan($plan);

        $user = auth()->user();
        $plan->load(['platforms' => fn ($query) => $query->orderBy('name')]);

        $pendingCrypto = PlanUpgradeRequest::query()
            ->where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('status', PlanUpgradeRequest::STATUS_PENDING)
            ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO)
            ->latest()
            ->first();

        return view('upgrade.show', [
            'user' => $user,
            'plan' => $plan,
            'catalog' => $catalog,
            'paymentReference' => $billing->paymentReference($user, $plan),
            'bankConfigured' => filled(config('billing.iban')),
            'cryptoAvailable' => $cryptoBilling->isAvailable(),
            'pendingCrypto' => $pendingCrypto,
        ]);
    }

    public function bank(Plan $plan, ManualBillingService $billing, PlanCatalogService $catalog): View
    {
        $billing->assertUpgradeablePlan($plan);

        $user = auth()->user();
        $plan->load(['platforms' => fn ($query) => $query->orderBy('name')]);

        return view('upgrade.bank', [
            'user' => $user,
            'plan' => $plan,
            'catalog' => $catalog,
            'paymentReference' => $billing->paymentReference($user, $plan),
            'bankConfigured' => filled(config('billing.iban')),
        ]);
    }

    public function storeBank(Request $request, Plan $plan, ManualBillingService $billing): RedirectResponse
    {
        $billing->assertUpgradeablePlan($plan);

        $validated = $request->validate([
            'payment_reference' => ['required', 'string', 'max:255'],
            'payer_note' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'confirmed' => ['accepted'],
        ]);

        $receiptPath = $billing->storeReceipt($request->file('receipt'), $request->user());

        $billing->submitRequest(
            $request->user(),
            $plan,
            $validated['payment_reference'],
            $validated['payer_note'] ?? null,
            $receiptPath,
        );

        return redirect()
            ->route('upgrade.index')
            ->with('status', 'Thanks — we received your payment details. We will activate your plan after verifying the bank transfer (usually within '.config('legal.support_response_hours').' hours).');
    }

    public function storeCrypto(Plan $plan, PlisioBillingService $cryptoBilling): RedirectResponse
    {
        $upgradeRequest = $cryptoBilling->createCheckout(auth()->user(), $plan);

        return redirect()->away($upgradeRequest->invoice_url);
    }

    public function cryptoSuccess(Plan $plan, PlanCatalogService $catalog): View
    {
        $user = auth()->user();

        return view('upgrade.crypto-success', [
            'user' => $user,
            'plan' => $plan,
            'catalog' => $catalog,
            'isActive' => $user->plan_id === $plan->id && $user->hasActiveSubscription(),
        ]);
    }
}
