<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Platform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::query()->with('platforms')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.create', [
            'platforms' => Platform::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $plan = Plan::query()->create($validated);
        $plan->platforms()->sync($request->input('platform_ids', []));

        return redirect()->route('admin.plans.index')->with('status', 'Plan created.');
    }

    public function edit(Plan $plan): View
    {
        $plan->load('platforms');

        return view('admin.plans.edit', [
            'plan' => $plan,
            'platforms' => Platform::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $this->validated($request, $plan);

        $plan->update($validated);
        $plan->platforms()->sync($request->input('platform_ids', []));

        return redirect()->route('admin.plans.index')->with('status', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->slug === 'free') {
            return back()->withErrors(['plan' => 'The free plan cannot be deleted.']);
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('status', 'Plan deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:plans,slug'.($plan ? ','.$plan->id : '')],
            'monthly_download_limit' => ['nullable', 'integer', 'min:1'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'billing_provider_plan_id' => ['nullable', 'string', 'max:150'],
            'platform_ids' => ['nullable', 'array'],
            'platform_ids.*' => ['integer', 'exists:platforms,id'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['monthly_download_limit'] = $validated['monthly_download_limit'] ?? null;
        $validated['price_cents'] = $validated['price_cents'] ?? null;
        $validated['billing_provider_plan_id'] = $validated['billing_provider_plan_id'] ?? null;

        return $validated;
    }
}
