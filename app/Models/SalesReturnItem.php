<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}