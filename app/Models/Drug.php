<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drug extends Model
{
    use HasFactory;

    protected $table = 'drugs';

    protected $fillable = [
        'name',
        'slug',
        'generic_name',
        'brand_name',
        'brand_id',
        'category_id',
        'image',

        // Pricing
        'box_price',
        'box_cost_price',
        'strip_price',
        'strip_cost_price',
        'tablet_price',
        'tablet_cost_price',

        // Packaging keys (VERY IMPORTANT)
        'strips_per_box',
        'tablets_per_strip',

        // Stock input (user enters only boxes)
        'quantity_in_boxes',

        // Auto-calculated values
        'total_strips',
        'total_tablets',
        'quantity',  // (quantity is total tablets)

        // Extra
        'expiry_date',
        'barcode',
        'status',
    ];

    protected $casts = [
        // Pricing
        'box_price'        => 'decimal:2',
        'box_cost_price'   => 'decimal:2',
        'strip_price'      => 'decimal:2',
        'strip_cost_price' => 'decimal:2',
        'tablet_price'     => 'decimal:2',
        'tablet_cost_price'=> 'decimal:2',

        // Packaging
        'strips_per_box'   => 'integer',
        'tablets_per_strip'=> 'integer',

        // Stock
        'quantity_in_boxes'=> 'integer',
        'total_strips'     => 'integer',
        'total_tablets'    => 'integer',
        'quantity'         => 'integer',

        'expiry_date'      => 'date',
        'category_id'      => 'integer',
        'brand_id'         => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Auto calculate stock numbers when saving.
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($drug) {

            // Ensure required packaging fields exist
            if ($drug->strips_per_box > 0 && $drug->tablets_per_strip > 0 && $drug->quantity_in_boxes > 0) {

                // Calculate strips
                $drug->total_strips = $drug->quantity_in_boxes * $drug->strips_per_box;

                // Calculate tablets
                $drug->total_tablets = $drug->total_strips * $drug->tablets_per_strip;

                // Set total quantity (tablets)
                $drug->quantity = $drug->total_tablets;
            }
        });
    }

    // Relationships
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days));
    }
}