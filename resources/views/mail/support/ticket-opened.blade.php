<x-mail::message>
# New support conversation

**{{ $ticket->user?->name }}** ({{ $ticket->user?->email }}) opened conversation **#{{ $ticket->id }}**.

**Subject:** {{ $ticket->subject }}

@if ($ticket->download_id)
**Download:** #{{ $ticket->download_id }}
@endif

---

{{ $message->body }}

<x-mail::button :url="$ticketUrl">
Open in admin
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
