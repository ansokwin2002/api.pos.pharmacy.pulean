<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'country',
        'status',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    // Relationships
    public function drugs(): HasMany
    {
        return $this->hasMany(Drug::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithDrugsCount($query)
    {
        return $query->withCount('drugs');
    }

    public function scopeWithActiveDrugsCount($query)
    {
        return $query->withCount(['drugs' => function ($query) {
            $query->where('status', 'active');
        }]);
    }

    // Accessors
    public function getDrugsCountAttribute()
    {
        return $this->drugs()->count();
    }

    public function getActiveDrugsCountAttribute()
    {
        return $this->drugs()->where('status', 'active')->count();
    }
}
