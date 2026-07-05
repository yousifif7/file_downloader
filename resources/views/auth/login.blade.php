@extends('layouts.auth')

@section('title', 'Sign in')
@section('heading', 'Welcome back')
@section('subheading', 'Sign in to access your downloads and account.')

@section('content')
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        @if (request('redirect'))
            <input type="hidden" name="redirect" value="{{ request('redirect') }}">
        @endif

        <div>
            <label for="email" class="label-dark">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input-dark mt-1.5">
            @error('email')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="label-dark">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="input-dark mt-1.5">
            @error('password')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-slate-600 bg-slate-900 text-violet-600 focus:ring-violet-500">
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-violet-400 hover:text-violet-300">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full !py-3">Sign in</button>

        <p class="text-center text-sm text-slate-500">
            No account?
            <a href="{{ route('register') }}" class="text-violet-400 font-medium hover:underline">Sign up free</a>
        </p>
    </form>
@endsection
