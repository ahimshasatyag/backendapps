<?php

namespace App\Models\So;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoDetail extends Model
{
    use HasFactory;

    protected $table = 'tb_so_dtl';
    protected $primaryKey = 'id_so_dtl'; // Assuming id_so_dtl or none, you can adjust
    
    public $timestamps = false;

    protected $guarded = [];
}
