@extends('layouts.auth')

@section('title', 'Verify email')
@section('heading', 'Verify your email')
@section('subheading', 'Thanks for signing up. Click the link in the email we sent you to activate your account.')

@section('content')
    @if (session('status') === 'verification-link-sent')
        <div class="mb-6 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 text-sm">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full !py-3">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-secondary w-full !py-3">Log out</button>
        </form>
    </div>
@endsection
