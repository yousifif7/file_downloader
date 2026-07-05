<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PlatformCookies;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => [
                'free_tier_limit' => Setting::getInt('free_tier_limit', 3),
                'max_file_size_mb' => Setting::getInt('max_file_size_mb', 500),
                'download_ttl_hours' => Setting::getInt('download_ttl_hours', 24),
                'maintenance_mode' => Setting::getBool('maintenance_mode', false),
                'maintenance_message' => Setting::getValue('maintenance_message', ''),
            ],
            'platformCookies' => PlatformCookies::status(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'free_tier_limit' => ['required', 'integer', 'min:1'],
            'max_file_size_mb' => ['required', 'integer', 'min:1'],
            'download_ttl_hours' => ['required', 'integer', 'min:1'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::setValue('free_tier_limit', (string) $validated['free_tier_limit']);
        Setting::setValue('max_file_size_mb', (string) $validated['max_file_size_mb']);
        Setting::setValue('download_ttl_hours', (string) $validated['download_ttl_hours']);
        Setting::setValue('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0');
        Setting::setValue('maintenance_message', $validated['maintenance_message'] ?? '');

        return back()->with('status', 'Settings saved.');
    }

    public function updatePlatformCookies(Request $request, string $platform): RedirectResponse
    {
        PlatformCookies::assertSupported($platform);

        $request->validate([
            'platform_cookies' => ['required', 'file', 'max:512'],
        ]);

        $content = (string) file_get_contents($request->file('platform_cookies')->getRealPath());

        if (! PlatformCookies::validateContent($platform, $content)) {
            return back()->withErrors([
                'platform_cookies' => 'Invalid file. Export cookies for '.PlatformCookies::supportedPlatforms()[$platform]['name'].' in Netscape format from a logged-in browser.',
            ]);
        }

        PlatformCookies::store($platform, $content);

        return back()->with('status', PlatformCookies::supportedPlatforms()[$platform]['name'].' cookies updated.');
    }
}
