<x-mail::message>
# Customer replied

**{{ $ticket->user?->name }}** replied on conversation **#{{ $ticket->id }}** ({{ $ticket->subject }}).

---

{{ $message->body }}

<x-mail::button :url="$ticketUrl">
Reply in admin
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
