<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $invoice_no
 * @property \Illuminate\Support\Carbon $invoice_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property int|null $customer_id
 * @property int|null $branch_id
 * @property int|null $store_id
 * @property numeric $subtotal
 * @property numeric $discount
 * @property numeric $tax
 * @property numeric $total_amount
 * @property numeric $paid_amount
 * @property numeric $due_amount
 * @property string $payment_method
 * @property int|null $payment_account_id
 * @property string $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Customer|null $customer
 * @property-read mixed $status_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SalesOrderItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereDueAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereInvoiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereInvoiceNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder wherePaymentAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SalesOrder extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'completed' => 'Paid',
            'partial'   => 'Partial',
            'pending'   => 'Unpaid',
            default     => ucfirst($this->status),
        };
    }
}
