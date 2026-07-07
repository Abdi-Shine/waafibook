<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sent_at'    => 'datetime',
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function targetCompany()
    {
        return $this->belongsTo(Company::class, 'target_company_id');
    }
}
