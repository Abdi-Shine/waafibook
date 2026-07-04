<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceSchedule extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'next_due_date' => 'date',
        'template_items' => 'array',
        'auto_invoice'  => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceOrders()
    {
        return $this->hasMany(ServiceOrder::class, 'schedule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function advanceNextDueDate(): void
    {
        $this->next_due_date = match($this->frequency) {
            'daily'     => $this->next_due_date->addDay(),
            'weekly'    => $this->next_due_date->addWeek(),
            'biweekly'  => $this->next_due_date->addWeeks(2),
            'monthly'   => $this->next_due_date->addMonth(),
            'quarterly' => $this->next_due_date->addMonths(3),
            'yearly'    => $this->next_due_date->addYear(),
            default     => $this->next_due_date->addMonth(),
        };

        if ($this->end_date && $this->next_due_date->gt($this->end_date)) {
            $this->status = 'ended';
        }

        $this->save();
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match($this->frequency) {
            'daily'     => 'Daily',
            'weekly'    => 'Weekly',
            'biweekly'  => 'Bi-weekly',
            'monthly'   => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly'    => 'Yearly',
            default     => ucfirst($this->frequency),
        };
    }
}
