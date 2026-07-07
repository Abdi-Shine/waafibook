<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireTrialSubscriptions extends Command
{
    protected $signature   = 'subscriptions:expire-trials';
    protected $description = 'Mark trial subscriptions whose expiry date has passed as expired';

    public function handle(): int
    {
        $count = Subscription::whereIn('status', ['trial', 'active'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
