<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'm_settings';
    public $timestamps = false;

    protected $fillable = [
        'setting_label',
        'setting_key',
        'setting_value',
        'setting_flag',
        'setting_note'
    ];
}
