<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetMonthlyQuotas extends Command
{
    protected $signature = 'quotas:reset';

    protected $description = 'Reset monthly download quotas for all users';

    public function handle(): int
    {
        $updated = User::query()->update([
            'downloads_this_month' => 0,
            'quota_reset_at' => now()->addMonth()->startOfMonth()->toDateString(),
        ]);

        $this->info("Reset quotas for {$updated} users.");

        return self::SUCCESS;
    }
}
