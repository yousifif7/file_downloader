<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformController extends Controller
{
    public function index(): View
    {
        return view('admin.platforms.index', [
            'platforms' => Platform::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Platform $platform): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
        ]);

        $platform->update($validated);

        return back()->with('status', 'Platform updated.');
    }
}
