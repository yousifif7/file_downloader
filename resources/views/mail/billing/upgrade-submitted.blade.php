<x-mail::message>
# Upgrade payment submitted

**{{ $upgradeRequest->user?->name }}** ({{ $upgradeRequest->user?->email }}) submitted a bank transfer for **{{ $upgradeRequest->plan?->name }}**.

**Amount:** {{ '$'.number_format(($upgradeRequest->plan?->price_cents ?? 0) / 100, 2) }} / month

**Payment reference entered:** {{ $upgradeRequest->payment_reference }}

@if ($upgradeRequest->payer_note)
**Customer note:** {{ $upgradeRequest->payer_note }}
@endif

@if ($upgradeRequest->receiptUrl())
**Receipt:** [View transfer screenshot]({{ $upgradeRequest->receiptUrl() }})
@endif

<x-mail::button :url="$reviewUrl">
Review in admin
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
