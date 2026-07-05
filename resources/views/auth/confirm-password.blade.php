@extends('layouts.auth')

@section('title', 'Confirm password')
@section('heading', 'Confirm your password')
@section('subheading', 'This is a secure area. Please confirm your password before continuing.')

@section('content')
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="label-dark">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="input-dark mt-1.5">
            @error('password')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary w-full !py-3">Confirm</button>
    </form>
@endsection
