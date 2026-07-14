<?php

use App\Mail\SubscriptionExpiryReminderMail;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

function makeCompanyWithSubscription(string $expiryDate, string $status = 'trial', array $subscriptionAttrs = []): Subscription
{
    $company = Company::create(['name' => 'Test Co ' . uniqid()]);

    User::withoutGlobalScopes()->create([
        'name'     => 'Faarax',
        'email'    => 'admin' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'role'     => 'admin',
        'company_id' => $company->id,
    ]);

    $plan = SubscriptionPlan::create([
        'name'          => 'Free Trial',
        'price'         => 0,
        'billing_cycle' => 'monthly',
        'max_users'     => 5,
        'status'        => 'active',
    ]);

    return Subscription::create(array_merge([
        'company_id'           => $company->id,
        'subscription_plan_id' => $plan->id,
        'start_date'           => now()->toDateString(),
        'expiry_date'          => $expiryDate,
        'status'                => $status,
    ], $subscriptionAttrs));
}

test('sends a reminder at each of the 7/5/3/1 day milestones', function (int $daysLeft) {
    Mail::fake();

    makeCompanyWithSubscription(now()->addDays($daysLeft)->toDateString());

    $this->artisan('subscriptions:send-expiry-reminders');

    Mail::assertSent(SubscriptionExpiryReminderMail::class, function ($mail) use ($daysLeft) {
        return $mail->daysLeft === $daysLeft && !$mail->isExpired;
    });
})->with([7, 5, 3, 1]);

test('does not send a reminder for a day count outside the milestone list', function () {
    Mail::fake();

    makeCompanyWithSubscription(now()->addDays(6)->toDateString());

    $this->artisan('subscriptions:send-expiry-reminders');

    Mail::assertNothingSent();
});

test('does not resend a reminder for a milestone already recorded', function () {
    Mail::fake();

    makeCompanyWithSubscription(now()->addDays(7)->toDateString(), 'trial', [
        'reminder_days_sent' => [7],
    ]);

    $this->artisan('subscriptions:send-expiry-reminders');

    Mail::assertNothingSent();
});

test('sends a one-time expired notice once the expiry date has passed, and never resends it', function () {
    Mail::fake();

    $subscription = makeCompanyWithSubscription(now()->subDay()->toDateString());

    $this->artisan('subscriptions:send-expiry-reminders');

    Mail::assertSent(SubscriptionExpiryReminderMail::class, fn ($mail) => $mail->isExpired === true);
    expect($subscription->fresh()->expiry_notice_sent_at)->not->toBeNull();

    Mail::fake();
    $this->artisan('subscriptions:send-expiry-reminders');

    Mail::assertNothingSent();
});
