<?php

namespace App\Models\So;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoDetailOption extends Model
{
    use HasFactory;

    protected $table = 'tb_so_dtl_options';
    protected $primaryKey = 'id_tb_so_dtl_options'; // Adjust if needed
    
    public $timestamps = false;

    protected $guarded = [];
}
