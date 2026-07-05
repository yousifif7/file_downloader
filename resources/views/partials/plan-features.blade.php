@props(['plan', 'catalog', 'plans' => null])

@php
    $allPlans = $plans ?? $catalog->activePlans();
@endphp

<ul {{ $attributes->merge(['class' => 'space-y-3.5 text-sm']) }}>
    @foreach ($catalog->featureBullets($plan, $allPlans) as $feature)
        <li class="flex items-start gap-2.5">
            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400 text-xs" aria-hidden="true">✓</span>
            <span class="min-w-0">
                <span class="text-slate-300">{{ $feature['text'] }}</span>
                @if ($feature['detail'])
                    <span class="block mt-0.5 text-xs text-slate-500 leading-relaxed">{{ $feature['detail'] }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ul>
