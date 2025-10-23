<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PodPatient extends Model
{
    use HasFactory;

    protected $table = 'pod_patients';

    protected $fillable = [
        'name',
        'gender',
        'age',
        'telephone',
        'address',
        'signs_of_life',
        'symptom',
        'diagnosis',
    ];
}
