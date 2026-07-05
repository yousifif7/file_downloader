@extends('layouts.admin')

@section('title', 'Plans')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-white">Plans</h1>
        <a href="{{ route('admin.plans.create') }}" class="btn-secondary justify-center sm:justify-start">New plan</a>
    </div>

    <div class="card admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th class="col-compact">Monthly limit</th>
                    <th>Platforms</th>
                    <th class="col-compact">Price</th>
                    <th class="col-compact">Billing mapping</th>
                    <th class="col-compact">Active</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td class="font-medium text-white">{{ $plan->name }}</td>
                        <td class="col-muted col-compact">{{ $plan->slug }}</td>
                        <td class="col-compact">{{ $plan->monthly_download_limit ?? 'Unlimited' }}</td>
                        <td class="col-wrap">
                            @if ($plan->platforms->isEmpty())
                                <span class="col-muted">—</span>
                            @else
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($plan->platforms as $platform)
                                        <span class="admin-badge">{{ $platform->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="col-compact">{{ $plan->price_cents ? '$'.number_format($plan->price_cents / 100, 2) : '—' }}</td>
                        <td class="col-muted col-compact">{{ $plan->billing_provider_plan_id ?? '—' }}</td>
                        <td class="col-compact">{{ $plan->is_active ? 'Yes' : 'No' }}</td>
                        <td class="col-actions">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="text-violet-400 hover:text-violet-300">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-slate-500 py-8">No plans yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
