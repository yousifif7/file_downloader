@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    <h1 class="text-2xl font-semibold text-white mb-6">Users</h1>

    <form method="GET" class="mb-4">
        <div class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search name or email" class="admin-input w-full sm:w-64">
            <button class="btn-secondary justify-center sm:justify-start">Search</button>
        </div>
    </form>

    <div class="card admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th class="col-compact">Plan</th>
                    <th class="col-compact">Subscription</th>
                    <th class="col-compact">Downloads</th>
                    <th class="col-compact">Admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}" class="font-medium text-violet-400 hover:text-violet-300">{{ $user->name }}</a>
                        </td>
                        <td class="col-truncate">{{ $user->email }}</td>
                        <td class="col-muted col-compact">{{ $user->plan?->name ?? '—' }}</td>
                        <td class="col-muted col-compact">{{ $user->subscriptionStatusLabel() }}</td>
                        <td class="col-compact">{{ $user->downloads_this_month }}</td>
                        <td class="col-compact">{{ $user->is_admin ? 'Yes' : 'No' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-slate-500 py-8">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
