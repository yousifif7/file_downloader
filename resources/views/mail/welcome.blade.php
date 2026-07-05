<x-mail::message>
# Welcome to {{ config('legal.business_name') }}

Hi {{ $user->name }},

Thanks for signing up. Your **{{ $planName }}** account is ready.

**Your plan includes:**
- {{ $monthlyLimit }}
- {{ $platforms }}
- Download history in your account dashboard

Paste a video link, pick a format, and save files to your device in a few clicks.

<x-mail::button :url="$homeUrl">
Start downloading
</x-mail::button>

You can check your quota and download history anytime from [My account]({{ $accountUrl }}).

Thanks,<br>
{{ config('legal.business_name') }}
</x-mail::message>
