@extends('layouts.auth')

@section('title', 'New password')
@section('heading', 'Choose a new password')
@section('subheading', 'Enter your email and a new password for your account.')

@section('content')
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="label-dark">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="input-dark mt-1.5">
            @error('email')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="label-dark">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="input-dark mt-1.5">
            @error('password')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="label-dark">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="input-dark mt-1.5">
            @error('password_confirmation')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary w-full !py-3">Reset password</button>

        <p class="text-center text-sm text-slate-500">
            <a href="{{ route('login') }}" class="text-violet-400 font-medium hover:underline">Back to sign in</a>
        </p>
    </form>
@endsection
