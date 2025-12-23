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
        'type_drug',
        'generic_name',
        'brand_name',
        'brand_id',
        'company_id', // Added company_id
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
            $stripsPerBox = $drug->strips_per_box ?? 1;
            $tabletsPerStrip = $drug->tablets_per_strip ?? 1;

            if ($stripsPerBox === 0) $stripsPerBox = 1;
            if ($tabletsPerStrip === 0) $tabletsPerStrip = 1;

            if ($drug->isDirty('total_tablets')) {
                // If total_tablets was directly modified, recalculate derived fields from it.
                $drug->quantity_in_boxes = floor($drug->total_tablets / ($stripsPerBox * $tabletsPerStrip));
                $drug->total_strips = floor($drug->total_tablets / $tabletsPerStrip);
            } elseif ($drug->isDirty('quantity_in_boxes') || $drug->isDirty('strips_per_box') || $drug->isDirty('tablets_per_strip') || $drug->exists === false) {
                // Original logic: recalculate from quantity_in_boxes if it's the input source
                if ($drug->quantity_in_boxes >= 0) {
                    $drug->total_strips = $drug->quantity_in_boxes * $stripsPerBox;
                    $drug->total_tablets = $drug->total_strips * $tabletsPerStrip;
                } else {
                    // If any of the inputs are invalid, set derived to zero
                    $drug->total_strips = 0;
                    $drug->total_tablets = 0;
                }
            }
        });
    }

    // Relationships
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days));
    }
}