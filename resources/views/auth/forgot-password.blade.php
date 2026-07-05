@extends('layouts.auth')

@section('title', 'Forgot password')
@section('heading', 'Reset your password')
@section('subheading', 'Enter your email and we will send you a link to choose a new password.')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="label-dark">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input-dark mt-1.5">
            @error('email')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary w-full !py-3">Email reset link</button>

        <p class="text-center text-sm text-slate-500">
            Remember your password?
            <a href="{{ route('login') }}" class="text-violet-400 font-medium hover:underline">Sign in</a>
        </p>
    </form>
@endsection
