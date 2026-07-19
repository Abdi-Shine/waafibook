<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiryReminderMail;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionExpiryReminders extends Command
{
    protected $signature   = 'subscriptions:send-expiry-reminders';
    protected $description = 'Email companies as their trial/subscription approaches expiry (7/5/3/1 days out), and a one-time notice once it has expired';

    private const REMINDER_DAYS = [7, 5, 3, 1];

    public function handle(): int
    {
        $subscriptions = Subscription::whereIn('status', ['trial', 'active'])
            ->whereNotNull('expiry_date')
            ->with('company')
            ->get();

        $sent = 0;

        foreach ($subscriptions as $subscription) {
            $company = $subscription->company;
            if (!$company) {
                continue;
            }

            $recipient = User::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('role', 'admin')
                ->first();

            $email = $recipient->email ?? $company->email;
            if (!$email) {
                continue;
            }

            $userName = $recipient->name ?? $company->name;
            $isTrial  = $subscription->status === 'trial';

            $expiry = Carbon::parse($subscription->expiry_date)->startOfDay();
            $today  = Carbon::today();

            // diffInDays defaults to signed in this Carbon version: positive
            // while expiry is still ahead, negative once it's in the past.
            $daysLeft = (int) $today->diffInDays($expiry, false);

            try {
                if ($daysLeft < 0) {
                    if ($subscription->expiry_notice_sent_at) {
                        continue;
                    }

                    Mail::to($email)->send(new SubscriptionExpiryReminderMail(
                        $company->name, $userName, $daysLeft, $expiry, $isTrial
                    ));

                    $subscription->update(['expiry_notice_sent_at' => now(), 'last_reminder_sent_at' => now()]);
                    $sent++;

                    // Throttle: sending a burst of emails back-to-back trips receiving
                    // mail servers' "too much mail from this IP" rate limits (confirmed
                    // in production logs), which damages the domain's sender reputation
                    // for all mail, not just these reminders.
                    usleep(1_500_000);

                    continue;
                }

                $reminderDaysSent = $subscription->reminder_days_sent ?? [];

                if (in_array($daysLeft, self::REMINDER_DAYS, true) && !in_array($daysLeft, $reminderDaysSent, true)) {
                    Mail::to($email)->send(new SubscriptionExpiryReminderMail(
                        $company->name, $userName, $daysLeft, $expiry, $isTrial
                    ));

                    $reminderDaysSent[] = $daysLeft;
                    $subscription->update(['reminder_days_sent' => $reminderDaysSent, 'last_reminder_sent_at' => now()]);
                    $sent++;

                    usleep(1_500_000);
                }
            } catch (\Exception $e) {
                Log::error("Subscription expiry reminder failed for company #{$company->id} ({$email}): " . $e->getMessage());
            }
        }

        $this->info("Sent {$sent} subscription expiry reminder(s).");

        return self::SUCCESS;
    }
}
