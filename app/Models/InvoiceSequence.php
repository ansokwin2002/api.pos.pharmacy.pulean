<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'date_key',
        'current_number',
    ];

    protected $casts = [
        'current_number' => 'integer',
    ];
}
