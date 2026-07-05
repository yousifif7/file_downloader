@extends('layouts.site')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-16">
        <h1 class="text-3xl font-bold text-white mb-2">@yield('page_title')</h1>
        <p class="text-sm text-slate-500 mb-10">Last updated: {{ config('legal.last_updated') }}</p>

        <div class="card p-8 space-y-8 text-slate-300 text-sm leading-relaxed">
            @yield('page_body')
        </div>

        <p class="text-center mt-8">
            <a href="{{ route('home') }}" class="text-sm text-violet-400 hover:underline">← Back to home</a>
        </p>
    </div>
@endsection
