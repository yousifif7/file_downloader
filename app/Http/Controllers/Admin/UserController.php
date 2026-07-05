<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\ManualBillingService;
use App\Services\DownloadQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim();

        $users = User::query()
            ->with('plan')
            ->when($search->isNotEmpty(), function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(20);

        $users->appends($request->query());

        return view('admin.users.index', compact('users', 'search'));
    }

    public function show(User $user): View
    {
        $user->load(['plan', 'downloads' => fn ($query) => $query->latest()->limit(20)]);

        return view('admin.users.show', [
            'user' => $user,
            'plans' => Plan::query()->where('is_active', true)->orderBy('id')->get(),
            'subscriptionStatuses' => User::subscriptionStatuses(),
        ]);
    }

    public function update(Request $request, User $user, DownloadQuotaService $quotaService): RedirectResponse
    {
        $validated = $request->validate([
            'is_admin' => ['sometimes', 'boolean'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'reset_quota' => ['sometimes', 'boolean'],
            'subscription_status' => ['nullable', 'string', 'in:'.implode(',', array_keys(User::subscriptionStatuses()))],
            'subscription_renews_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $user->is_admin = $request->boolean('is_admin');

        if ($request->boolean('reset_quota')) {
            $user->downloads_this_month = 0;
            $user->quota_reset_at = now()->addMonth()->startOfMonth();
        }

        if (array_key_exists('plan_id', $validated)) {
            $user->plan_id = $validated['plan_id'];
        }

        $user->subscription_status = $validated['subscription_status'] ?? null;
        $user->subscription_renews_at = $validated['subscription_renews_at'] ?? null;
        $user->subscription_ends_at = $validated['subscription_ends_at'] ?? null;

        $assignedPlan = $user->plan_id ? Plan::query()->find($user->plan_id) : null;
        if ($assignedPlan && $assignedPlan->slug !== 'free' && $user->subscription_status === null) {
            $user->subscription_status = User::SUBSCRIPTION_ACTIVE;
            $user->billing_provider = config('billing.provider');
        }

        $user->save();

        return back()->with('status', 'User updated.');
    }

    public function grantComplimentary(Request $request, User $user, ManualBillingService $billing): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'subscription_ends_at' => ['nullable', 'date', 'after:today'],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);
        $endsAt = isset($validated['subscription_ends_at'])
            ? \Carbon\Carbon::parse($validated['subscription_ends_at'])->endOfDay()
            : null;

        $billing->grantComplimentary($user, $plan, $endsAt);

        return back()->with('status', "Granted complimentary {$plan->name} access to {$user->email}.");
    }

    public function grantPaidManual(Request $request, User $user, ManualBillingService $billing): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'subscription_renews_at' => ['nullable', 'date', 'after:today'],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);
        $renewsAt = isset($validated['subscription_renews_at'])
            ? \Carbon\Carbon::parse($validated['subscription_renews_at'])
            : null;

        $billing->grantPaidManual($user, $plan, $renewsAt);

        return back()->with('status', "Granted paid {$plan->name} access to {$user->email}.");
    }

    public function revokePlan(User $user, ManualBillingService $billing): RedirectResponse
    {
        $billing->revokeToFree($user);

        return back()->with('status', "{$user->email} was moved back to the Free plan.");
    }
}
