<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceQuotation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'valid_until' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(ServiceQuotationItem::class);
    }

    public function convertedOrder()
    {
        return $this->belongsTo(ServiceOrder::class, 'converted_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft'     => 'bg-gray-100 text-gray-700',
            'sent'      => 'bg-blue-100 text-blue-800',
            'accepted'  => 'bg-green-100 text-green-800',
            'declined'  => 'bg-red-100 text-red-700',
            'converted' => 'bg-lime-100 text-lime-800',
            default     => 'bg-gray-100 text-gray-700',
        };
    }

    public static function nextQuoteNumber(int $companyId): string
    {
        $last = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('quote_number');

        $num = $last ? ((int) ltrim(substr($last, 3), '0') + 1) : 1;
        return 'QT-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
