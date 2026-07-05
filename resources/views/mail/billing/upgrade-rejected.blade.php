<x-mail::message>
# We could not approve your payment

Hi {{ $upgradeRequest->user?->name }},

We reviewed your bank transfer for **{{ $upgradeRequest->plan?->name }}**, but we were not able to verify it yet. Your current plan has not changed.

@if ($upgradeRequest->admin_note)
**Reason from our team:**

{{ $upgradeRequest->admin_note }}
@else
If you believe this was a mistake, reply to this email or contact support with your transfer receipt and payment reference **{{ $upgradeRequest->payment_reference }}**.
@endif

You can send a corrected transfer and submit a new payment from the upgrade page. Include the exact payment reference shown there.

<x-mail::button :url="$upgradeUrl">
Try again
</x-mail::button>

Questions? [Contact support]({{ $supportUrl }}).

Thanks,<br>
{{ config('legal.business_name') }}
</x-mail::message>
