@extends('layouts.auth')

@section('title', 'Sign up')
@section('heading', 'Create your account')
@section('subheading')
    @if ($freePlan)
        Free forever. {{ $catalog->monthlyLimitLabel($freePlan) }} on {{ $catalog->platformNamesLabel($freePlan) }}.
    @else
        Free forever. Create an account to start downloading.
    @endif
@endsection

@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="label-dark">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="input-dark mt-1.5">
            @error('name')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="label-dark">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="input-dark mt-1.5">
            @error('email')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="label-dark">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="input-dark mt-1.5">
            @error('password')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="label-dark">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="input-dark mt-1.5">
        </div>

        <label class="flex items-start gap-3 text-sm text-slate-400">
            <input type="checkbox" name="terms" value="1" required @checked(old('terms')) class="mt-1 rounded border-slate-600 bg-slate-900 text-violet-500 focus:ring-violet-500">
            <span>
                I agree to the <a href="{{ route('terms') }}" class="text-violet-400 hover:underline" target="_blank" rel="noopener">Terms of Service</a>
                and <a href="{{ route('privacy') }}" class="text-violet-400 hover:underline" target="_blank" rel="noopener">Privacy Policy</a>.
            </span>
        </label>
        @error('terms')<p class="text-sm text-red-400">{{ $message }}</p>@enderror

        <button type="submit" class="btn-primary w-full !py-3">Create account</button>

        <p class="text-center text-sm text-slate-500">
            Already have an account?
            <a href="{{ route('login') }}" class="text-violet-400 font-medium hover:underline">Sign in</a>
        </p>
    </form>
@endsection
