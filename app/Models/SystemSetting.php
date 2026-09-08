<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'login_logo',
        'system_logo',
        'recharge_audit',
        'tax_rate',
    ];

    protected $casts = [
        'recharge_audit' => 'boolean',
        'tax_rate' => 'decimal:2',
    ];

    /**
     * Get the (single) system settings row, creating it on first use.
     */
    public static function row(): self
    {
        return static::query()->firstOrCreate([], [
            'recharge_audit' => false,
            'tax_rate' => 10,
        ]);
    }
}
