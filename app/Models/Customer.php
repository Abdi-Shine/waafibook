<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $customer_code
 * @property string $customer_type
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property int|null $account_id
 * @property string|null $account_type
 * @property string|null $account_code
 * @property string $opening_balance_type
 * @property numeric|null $credit_limit
 * @property numeric $amount_balance
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account|null $account
 * @property-read mixed $full_name
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\PaymentIn> $payments
 * @property-read int|null $payments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAccountCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAccountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAmountBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreditLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCustomerCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCustomerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereOpeningBalanceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Customer extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentIn::class);
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\SalesOrder::class, 'customer_id');
    }
}
