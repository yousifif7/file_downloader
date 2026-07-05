<form method="POST" action="{{ $plan ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="card p-6 max-w-xl space-y-4">
    @csrf
    @if ($plan)
        @method('PUT')
    @endif

    <div>
        <label class="label-dark mb-1">Name</label>
        <input type="text" name="name" value="{{ old('name', $plan?->name) }}" class="admin-input" required>
    </div>

    <div>
        <label class="label-dark mb-1">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $plan?->slug) }}" class="admin-input" required>
    </div>

    <div>
        <label class="label-dark mb-1">Monthly download limit</label>
        <input type="number" name="monthly_download_limit" value="{{ old('monthly_download_limit', $plan?->monthly_download_limit) }}" class="admin-input" min="1">
        <p class="text-xs text-slate-500 mt-1">Leave empty for unlimited.</p>
    </div>

    <div>
        <label class="label-dark mb-1">Price (cents)</label>
        <input type="number" name="price_cents" value="{{ old('price_cents', $plan?->price_cents) }}" class="admin-input" min="0">
        <p class="text-xs text-slate-500 mt-1">Monthly price in cents. Leave empty for a non-billable placeholder plan.</p>
    </div>

    <div>
        <label class="label-dark mb-1">Billing provider plan ID</label>
        <input type="text" name="billing_provider_plan_id" value="{{ old('billing_provider_plan_id', $plan?->billing_provider_plan_id) }}" class="admin-input">
        <p class="text-xs text-slate-500 mt-1">Store the external monthly price identifier here once checkout is connected.</p>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-300">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="admin-checkbox" @checked(old('is_active', $plan?->is_active ?? true))>
        Active
    </label>

    <div>
        <label class="label-dark mb-2">Included platforms</label>
        <p class="text-xs text-slate-500 mb-3">Users on this plan can only download from the platforms you select here. The platform must also be enabled globally. These appear on the public pricing page.</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @php($selectedPlatformIds = collect(old('platform_ids', $plan?->platforms->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id))
            @foreach ($platforms ?? [] as $platform)
                <label class="flex items-center gap-2 rounded-xl border border-slate-800 px-3 py-2 text-sm text-slate-300">
                    <input
                        type="checkbox"
                        name="platform_ids[]"
                        value="{{ $platform->id }}"
                        class="admin-checkbox"
                        @checked($selectedPlatformIds->contains($platform->id))
                    >
                    <span>{{ $platform->name }}</span>
                    @if (! $platform->is_enabled)
                        <span class="text-xs text-slate-500">(globally off)</span>
                    @endif
                </label>
            @endforeach
        </div>
    </div>

    <button class="btn-primary">Save plan</button>
</form>
