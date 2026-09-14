<?php

namespace App\Models\Counter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    protected $table = 'm_counter';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id_counter',
        'periode',
        'no_counter'
    ];
}
