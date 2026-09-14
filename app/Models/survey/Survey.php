<?php

namespace App\Models\survey;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $table = 'tb_survey';
    protected $primaryKey = 'id_survey';
    
    public $incrementing = true;
    public $timestamps = false;

    protected $guarded = [];
}
