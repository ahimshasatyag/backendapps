<?php

namespace App\Models\salesretur;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salesretur extends Model
{
    use HasFactory;

    protected $table = 'tb_retur_penjualan_hdr';
    protected $primaryKey = 'id';
    
    public $incrementing = true;
    public $timestamps = false;

    protected $guarded = [];
}
