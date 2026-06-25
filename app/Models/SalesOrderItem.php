<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $sales_order_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string|null $product_code
 * @property numeric $unit_price
 * @property numeric $quantity
 * @property string $unit
 * @property numeric $discount
 * @property numeric $total_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SalesOrder $order
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereProductCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereProductName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereSalesOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrderItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SalesOrderItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnItems()
    {
        return $this->hasMany(SalesReturnItem::class, 'sales_order_item_id');
    }
}
