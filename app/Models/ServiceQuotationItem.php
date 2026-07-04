<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceQuotationItem extends Model
{
    protected $guarded = [];

    public function quotation()
    {
        return $this->belongsTo(ServiceQuotation::class, 'service_quotation_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
