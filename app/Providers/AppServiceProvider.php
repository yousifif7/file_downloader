<?php

namespace App\Providers;

use App\Models\PlanUpgradeRequest;
use App\Models\SupportTicket;
use App\Services\PlisioBillingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin', function ($view): void {
            app(PlisioBillingService::class)->expireStaleCryptoRequests();

            $view->with([
                'adminPendingUpgradeCount' => PlanUpgradeRequest::query()
                    ->where('status', PlanUpgradeRequest::STATUS_PENDING)
                    ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_BANK)
                    ->count(),
                'adminAwaitingTicketCount' => SupportTicket::query()
                    ->where('status', SupportTicket::STATUS_OPEN)
                    ->where('awaiting_staff', true)
                    ->count(),
            ]);
        });
    }
}
