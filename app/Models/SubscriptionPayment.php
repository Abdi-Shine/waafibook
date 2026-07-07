<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $subscription_id
 * @property numeric $amount
 * @property string $payment_date
 * @property string $payment_method
 * @property string|null $transaction_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Subscription $subscription
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id', 'amount', 'payment_date',
        'payment_method', 'transaction_id', 'status', 'notes',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
