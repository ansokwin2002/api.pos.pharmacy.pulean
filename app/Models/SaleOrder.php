<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleOrder extends Model
{
    use HasFactory;

    protected $table = 'sales_orders';

    protected $fillable = [
        'invoice_number',
        'customer_name',
        'customer_phone',
        'payment_method',
        'subtotal',
        'discount',
        'tax',
        'total',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'total' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleOrderItem::class, 'sales_order_id');
    }
}
