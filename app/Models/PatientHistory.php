<?php

namespace App\Models;

use App\Helpers\HashidsHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'json_data',
    ];

    protected $appends = ['hashid'];

    public function getHashidAttribute(): string
    {
        return HashidsHelper::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = HashidsHelper::decode($value);
        if ($decoded !== null) {
            return parent::resolveRouteBinding($decoded, $field);
        }

        return parent::resolveRouteBinding($value, $field);
    }
}

