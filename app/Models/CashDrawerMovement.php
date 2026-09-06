<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDrawerMovement extends Model
{
    use HasFactory;

    protected $table = 'cash_drawer_movements';

    protected $fillable = [
        'cash_drawer_id',
        'type',
        'label',
        'invoice',
        'amount',
        'change',
        'client_id',
        'user',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'change' => 'float',
        'occurred_at' => 'datetime',
    ];

    public function drawer(): BelongsTo
    {
        return $this->belongsTo(CashDrawer::class, 'cash_drawer_id');
    }
}