@extends('layouts.site')

@section('title', 'Support')

@section('meta_description', 'Contact support for '.config('legal.business_name').'. Log in to chat with our team about downloads, billing, and refunds.')

@section('canonical', route('support'))

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-16">
        <h1 class="text-3xl font-bold text-white mb-2">Support</h1>
        <p class="text-slate-400 mb-10">
            Sign in to open a support conversation. Our team replies in your account, and you get an email when we respond.
        </p>

        <div class="grid gap-6 sm:grid-cols-2 mb-10">
            <div class="card p-6">
                <h2 class="text-sm font-semibold text-white mb-2">Account support</h2>
                <p class="text-sm text-slate-400 mb-4">Logged-in users can chat with us about downloads, billing, and refunds.</p>
                <a href="{{ route('login', ['redirect' => '/support/tickets']) }}" class="btn-primary w-full justify-center">Log in to contact support</a>
                <p class="text-center text-sm text-slate-500 mt-4">
                    No account?
                    <a href="{{ route('register') }}" class="text-violet-400 hover:underline">Sign up free</a>
                </p>
            </div>
            <div class="card p-6">
                <h2 class="text-sm font-semibold text-white mb-2">Response time</h2>
                <p class="text-sm text-slate-400">
                    We usually reply within {{ config('legal.support_response_hours') }} hours on business days.
                </p>
                <p class="text-sm text-slate-500 mt-4">
                    Public contact: <a href="mailto:{{ config('legal.support_email') }}" class="text-violet-400 hover:underline">{{ config('legal.support_email') }}</a>
                </p>
            </div>
        </div>

        <div class="card p-6 text-sm text-slate-400 space-y-2">
            <p class="text-slate-300 font-medium">Before you write</p>
            <p>Check our <a href="{{ route('home') }}#faq" class="text-violet-400 hover:underline">FAQ</a> for common download issues.</p>
            <p>Refund rules are in our <a href="{{ route('refund') }}" class="text-violet-400 hover:underline">Refund Policy</a>.</p>
        </div>

        <p class="text-center mt-8">
            <a href="{{ route('home') }}" class="text-sm text-violet-400 hover:underline">← Back to home</a>
        </p>
    </div>
@endsection
