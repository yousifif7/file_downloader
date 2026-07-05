<?php

namespace App\Mail;

use App\Models\User;
use App\Services\PlanCatalogService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('legal.business_name'),
        );
    }

    public function content(): Content
    {
        $catalog = app(PlanCatalogService::class);
        $plan = $this->user->plan ?? $catalog->freePlan();

        return new Content(
            markdown: 'mail.welcome',
            with: [
                'planName' => $plan?->name ?? 'Free',
                'monthlyLimit' => $catalog->monthlyLimitLabel($plan),
                'platforms' => $catalog->platformNamesLabel($plan),
                'homeUrl' => route('home').'#download',
                'accountUrl' => route('account'),
            ],
        );
    }
}
