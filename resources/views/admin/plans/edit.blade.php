@extends('layouts.admin')

@section('title', 'Edit plan')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Edit plan</h1>
    @include('admin.plans._form', ['plan' => $plan, 'platforms' => $platforms])

    @php($catalog = app(\App\Services\PlanCatalogService::class))
    <div class="mt-8 max-w-xl">
        <h2 class="text-sm font-semibold text-slate-300 mb-3">Pricing page preview</h2>
        @include('partials.plan-pricing-card', [
            'plan' => $plan,
            'catalog' => $catalog,
            'plans' => $catalog->activePlans(),
            'currentPlan' => null,
        ])
        <p class="text-xs text-slate-500 mt-2">Features are generated from this plan's limit, platforms, and price.</p>
    </div>
@endsection
