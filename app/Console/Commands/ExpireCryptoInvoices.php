<?php

namespace App\Console\Commands;

use App\Services\PlisioBillingService;
use Illuminate\Console\Command;

class ExpireCryptoInvoices extends Command
{
    protected $signature = 'billing:expire-crypto-invoices';

    protected $description = 'Cancel stale pending crypto upgrade requests after invoice expiry';

    public function handle(PlisioBillingService $billing): int
    {
        $expired = $billing->expireStaleCryptoRequests();

        if ($expired > 0) {
            $this->info("Cancelled {$expired} expired crypto invoice(s).");
        }

        return self::SUCCESS;
    }
}
