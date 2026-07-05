<x-mail::message>
# We replied to your support request

Hi {{ $ticket->user?->name }},

Our team replied to **{{ $ticket->subject }}**.

---

{{ $message->body }}

<x-mail::button :url="$ticketUrl">
View conversation and reply
</x-mail::button>

You can continue the conversation on your support page. We usually reply within {{ config('legal.support_response_hours') }} hours on business days.

Thanks,<br>
{{ config('legal.business_name') }}
</x-mail::message>
