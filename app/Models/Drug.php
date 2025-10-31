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
        'unit',
        'price',
        'cost_price',
        'quantity',
        'expiry_date',
        'barcode',
        'manufacturer',
        'dosage',
        'instructions',
        'side_effects',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'quantity' => 'integer',
        'expiry_date' => 'date',
        'category_id' => 'integer',
        'brand_id' => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

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
