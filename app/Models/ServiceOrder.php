<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceOrder extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(ServiceOrderItem::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'service_order_employees')
                    ->withPivot('role', 'assigned_at')
                    ->withTimestamps();
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function quotation()
    {
        return $this->belongsTo(ServiceQuotation::class, 'quotation_id');
    }

    public function schedule()
    {
        return $this->belongsTo(ServiceSchedule::class, 'schedule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending'     => 'bg-yellow-100 text-yellow-800',
            'confirmed'   => 'bg-blue-100 text-blue-800',
            'in_progress' => 'bg-lime-100 text-lime-800',
            'completed'   => 'bg-green-100 text-green-800',
            'cancelled'   => 'bg-red-100 text-red-800',
            default       => 'bg-gray-100 text-gray-700',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'bg-red-100 text-red-700',
            'high'   => 'bg-orange-100 text-orange-700',
            'normal' => 'bg-blue-100 text-blue-700',
            'low'    => 'bg-gray-100 text-gray-500',
            default  => 'bg-gray-100 text-gray-500',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->scheduled_date
            && $this->scheduled_date->isPast()
            && !in_array($this->status, ['completed', 'cancelled']);
    }

    public static function nextOrderNumber(int $companyId): string
    {
        $last = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('order_number');

        $num = $last ? ((int) ltrim(substr($last, 4), '0') + 1) : 1;
        return 'SRV-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
