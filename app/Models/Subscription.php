<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $company_id
 * @property int $subscription_plan_id
 * @property string $start_date
 * @property string $expiry_date
 * @property string $status
 * @property string|null $payment_method
 * @property int $auto_renew
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SubscriptionPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\SubscriptionPlan $plan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereAutoRenew($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereSubscriptionPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Subscription extends Model
{
    protected $fillable = [
        'company_id', 'subscription_plan_id', 'start_date', 'expiry_date',
        'status', 'payment_method', 'auto_renew', 'reminder_days_sent', 'expiry_notice_sent_at'
    ];

    protected $casts = [
        'reminder_days_sent'     => 'array',
        'expiry_notice_sent_at'  => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isActive()
    {
        return $this->status === 'active'
            && \Carbon\Carbon::parse($this->expiry_date)->endOfDay()->isFuture();
    }

    // Broader than isActive(): also covers an unexpired trial. Used to
    // gate write access app-wide once a trial or paid term has actually run out.
    public function hasAccess(): bool
    {
        if (in_array($this->status, ['cancelled', 'expired'])) {
            return false;
        }

        return !$this->expiry_date || \Carbon\Carbon::parse($this->expiry_date)->endOfDay()->isFuture();
    }
}
