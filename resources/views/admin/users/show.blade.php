@extends('layouts.admin')

@section('title', $user->name)

@section('content')
    <h1 class="text-2xl font-semibold text-white mb-6">{{ $user->name }}</h1>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="card p-6 text-sm space-y-2 text-slate-200">
            <div><span class="text-slate-500 w-40 inline-block">Email</span> {{ $user->email }}</div>
            <div><span class="text-slate-500 w-40 inline-block">Plan</span> {{ $user->plan?->name ?? '—' }}</div>
            <div><span class="text-slate-500 w-40 inline-block">Access</span> {{ $user->subscriptionStatusLabel() }}</div>
            <div><span class="text-slate-500 w-40 inline-block">Billing</span> {{ $user->billing_provider ?? '—' }}</div>
            <div><span class="text-slate-500 w-40 inline-block">Renews at</span> {{ $user->subscription_renews_at?->format('Y-m-d H:i') ?? '—' }}</div>
            <div><span class="text-slate-500 w-40 inline-block">Ends at</span> {{ $user->subscription_ends_at?->format('Y-m-d H:i') ?? '—' }}</div>
            <div><span class="text-slate-500 w-40 inline-block">Downloads this month</span> {{ $user->downloads_this_month }}</div>
            <div><span class="text-slate-500 w-40 inline-block">Quota resets</span> {{ $user->quota_reset_at?->format('Y-m-d') ?? '—' }}</div>
            <div><span class="text-slate-500 w-40 inline-block">Joined</span> {{ $user->created_at->format('Y-m-d') }}</div>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="font-medium text-white mb-2">Grant plan access</h2>
                <p class="text-xs text-slate-500 mb-4">Give a plan directly — for family, testers, or manual overrides. No bank transfer required for complimentary access.</p>

                <form method="POST" action="{{ route('admin.users.grant-complimentary', $user) }}" class="space-y-3 mb-6 pb-6 border-b border-slate-800">
                    @csrf
                    <p class="text-sm font-medium text-violet-300">Complimentary (free access)</p>
                    <select name="plan_id" class="admin-input" required>
                        @foreach ($plans->where('slug', '!=', 'free') as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                    <div>
                        <label class="label-dark mb-1">Optional end date</label>
                        <input type="date" name="subscription_ends_at" class="admin-input" placeholder="Leave empty for no expiry">
                        <p class="text-xs text-slate-500 mt-1">Leave empty to keep access until you revoke it manually.</p>
                    </div>
                    <button class="btn-primary w-full justify-center !py-2 text-sm">Grant complimentary access</button>
                </form>

                <form method="POST" action="{{ route('admin.users.grant-paid', $user) }}" class="space-y-3 mb-6 pb-6 border-b border-slate-800">
                    @csrf
                    <p class="text-sm font-medium text-slate-300">Paid (manual / bank transfer)</p>
                    <select name="plan_id" class="admin-input" required>
                        @foreach ($plans->where('slug', '!=', 'free')->where('price_cents', '>', 0) as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — ${{ number_format($plan->price_cents / 100, 2) }}/mo</option>
                        @endforeach
                    </select>
                    <div>
                        <label class="label-dark mb-1">Renews at</label>
                        <input type="date" name="subscription_renews_at" class="admin-input" value="{{ now()->addMonth()->format('Y-m-d') }}">
                    </div>
                    <button class="btn-secondary w-full justify-center !py-2 text-sm">Grant paid access</button>
                </form>

                <form method="POST" action="{{ route('admin.users.revoke-plan', $user) }}" onsubmit="return confirm('Move this user back to the Free plan?')">
                    @csrf
                    <button type="submit" class="btn-secondary w-full justify-center !py-2 text-sm text-red-300 border-red-500/30 hover:border-red-500/50">Revoke to Free plan</button>
                </form>
            </div>

            <div class="card p-6">
                <h2 class="font-medium text-white mb-4">Advanced settings</h2>
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="hidden" name="is_admin" value="0">
                        <input type="checkbox" name="is_admin" value="1" class="admin-checkbox" @checked($user->is_admin)>
                        Administrator
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="reset_quota" value="1" class="admin-checkbox">
                        Reset monthly download quota
                    </label>
                    <div>
                        <label class="label-dark mb-1">Plan (raw)</label>
                        <select name="plan_id" class="admin-input">
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('plan_id', $user->plan_id) == $plan->id)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label-dark mb-1">Subscription status</label>
                        <select name="subscription_status" class="admin-input">
                            <option value="">Free / manual</option>
                            @foreach ($subscriptionStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('subscription_status', $user->subscription_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label-dark mb-1">Renews at</label>
                            <input type="date" name="subscription_renews_at" value="{{ old('subscription_renews_at', $user->subscription_renews_at?->format('Y-m-d')) }}" class="admin-input">
                        </div>
                        <div>
                            <label class="label-dark mb-1">Ends at</label>
                            <input type="date" name="subscription_ends_at" value="{{ old('subscription_ends_at', $user->subscription_ends_at?->format('Y-m-d')) }}" class="admin-input">
                        </div>
                    </div>
                    <button class="btn-primary">Save advanced settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6 card overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-800 font-medium text-white">Recent downloads</div>
        <div class="divide-y divide-slate-800">
            @forelse ($user->downloads as $download)
                <a href="{{ route('admin.downloads.show', $download) }}" class="block px-4 py-3 hover:bg-slate-800/40 text-sm transition">
                    <div class="truncate text-slate-200">{{ $download->media_title ?: $download->source_url }}</div>
                    <div class="text-slate-500">{{ $download->status }} · {{ $download->created_at->diffForHumans() }}</div>
                </a>
            @empty
                <div class="px-4 py-6 text-slate-500 text-sm">No downloads.</div>
            @endforelse
        </div>
    </div>
@endsection
