<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashDrawer extends Model
{
    use HasFactory;

    protected $table = 'cash_drawers';

    protected $fillable = [
        'session_date',
        'opening_float',
        'opened_at',
        'closed_at',
        'is_closed',
        'expected',
        'counted',
        'difference',
    ];

    protected $casts = [
        'session_date' => 'date',
        'opening_float' => 'float',
        'expected' => 'float',
        'counted' => 'float',
        'difference' => 'float',
        'is_closed' => 'boolean',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(CashDrawerMovement::class, 'cash_drawer_id')->orderBy('occurred_at');
    }

    public function cashSalesTotal(): float
    {
        return round((float) $this->movements()->where('type', 'cash_sale')->sum('amount'), 2);
    }

    public function cashInTotal(): float
    {
        return round((float) $this->movements()->where('type', 'cash_in')->sum('amount'), 2);
    }

    public function cashOutTotal(): float
    {
        return round((float) $this->movements()->where('type', 'cash_out')->sum('amount'), 2);
    }

    public function changeGivenTotal(): float
    {
        return round((float) $this->movements()->sum('change'), 2);
    }

    public function expectedTotal(): float
    {
        return round(
            $this->opening_float
            + $this->cashSalesTotal()
            + $this->cashInTotal()
            - $this->cashOutTotal(),
            2
        );
    }
}