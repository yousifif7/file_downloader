<x-mail::message>
# Your plan is active

Hi {{ $upgradeRequest->user?->name }},

Your payment was approved and **{{ $upgradeRequest->plan?->name }}** is now active on your account.

Your plan renews on **{{ $upgradeRequest->user?->subscription_renews_at?->format('M j, Y') }}**. Send us a new bank transfer before that date to keep access.

<x-mail::button :url="$accountUrl">
Go to my account
</x-mail::button>

Thanks,<br>
{{ config('legal.business_name') }}
</x-mail::message>
