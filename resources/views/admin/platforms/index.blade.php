@extends('layouts.admin')

@section('title', 'Platforms')

@section('content')
    <h1 class="text-2xl font-semibold text-white mb-6">Platforms</h1>

    <div class="card admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="col-compact">Slug</th>
                    <th>Extractor</th>
                    <th class="col-compact">Enabled</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($platforms as $platform)
                    <tr>
                        <td class="font-medium text-white">{{ $platform->name }}</td>
                        <td class="col-muted col-compact">{{ $platform->slug }}</td>
                        <td class="col-muted" title="{{ $platform->extractor_class }}">
                            <span class="font-mono text-xs">{{ class_basename($platform->extractor_class) }}</span>
                        </td>
                        <td class="col-compact">
                            <form method="POST" action="{{ route('admin.platforms.update', $platform) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_enabled" value="0">
                                <label class="inline-flex items-center gap-2 text-slate-300">
                                    <input type="checkbox" name="is_enabled" value="1" class="admin-checkbox" @checked($platform->is_enabled) onchange="this.form.submit()">
                                    <span>{{ $platform->is_enabled ? 'On' : 'Off' }}</span>
                                </label>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
