<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleOrderItem extends Model
{
    use HasFactory;

    protected $table = 'sales_order_items';

    protected $fillable = [
        'sales_order_id',
        'drug_id',
        'drug_name',
        'unit_type',
        'price',
        'qty',
        'subtotal',
    ];

    protected $casts = [
        'price' => 'float',
        'qty' => 'integer',
        'subtotal' => 'float',
    ];

    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }
}
