<?php

namespace App\Models\Do;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoDtl extends Model
{
    use HasFactory;

    protected $table = 'tb_do_dtl';
    protected $primaryKey = 'id_do_dtl';
    
    public $timestamps = false;

    protected $guarded = [];
}
