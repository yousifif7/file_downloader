<?php

namespace App\Http\Controllers\Auth;

use App\Mail\WelcomeMail;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Platform;
use App\Models\User;
use App\Services\PlanCatalogService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(PlanCatalogService $catalog): View
    {
        $freePlan = $catalog->freePlan();

        return view('auth.register', [
            'freePlan' => $freePlan,
            'catalog' => $catalog,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $freePlan = Plan::query()->where('slug', 'free')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'plan_id' => $freePlan?->id,
            'downloads_this_month' => 0,
            'quota_reset_at' => now()->addMonth()->startOfMonth(),
        ]);

        $user->load('plan');

        Mail::to($user->email)->send(new WelcomeMail($user));

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('home', absolute: false).'#download');
    }
}
