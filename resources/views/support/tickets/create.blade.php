@extends('layouts.site')

@section('title', 'New support conversation')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <a href="{{ route('support.tickets.index') }}" class="text-sm text-violet-400 hover:text-violet-300">← Back to support</a>
        <h1 class="text-3xl font-bold text-white mt-4 mb-8">New conversation</h1>

        <div class="card p-8">
            <form method="POST" action="{{ route('support.tickets.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="subject" class="label-dark">Topic</label>
                    <select id="subject" name="subject" required class="input-dark mt-1.5">
                        @php
                            $subjects = [
                                'Download failed' => 'Download failed',
                                'Billing or subscription' => 'Billing or subscription',
                                'Refund request' => 'Refund request',
                                'Account access' => 'Account access',
                                'Other' => 'Other',
                            ];
                        @endphp
                        @foreach ($subjects as $value => $label)
                            <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('subject')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="download_id" class="label-dark">Download ID <span class="text-slate-500">(optional)</span></label>
                    <input id="download_id" type="text" name="download_id" value="{{ old('download_id', $downloadId) }}" inputmode="numeric" pattern="[0-9]*" class="input-dark mt-1.5" placeholder="e.g. 42">
                    @error('download_id')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="message" class="label-dark">How can we help?</label>
                    <textarea id="message" name="message" rows="6" required class="input-dark mt-1.5 min-h-[140px]" placeholder="Describe the issue and include any relevant links or download IDs.">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-primary">Start conversation</button>
            </form>
        </div>
    </div>
@endsection
