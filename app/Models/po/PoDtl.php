<?php

namespace App\Models\po;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoDtl extends Model
{
    use HasFactory;

    protected $table = 'tb_po_dtl';
    protected $primaryKey = 'id_po_dtl';
    public $timestamps = false;

    protected $fillable = [
        'id_po',
        'id_product',
        'code_product',
        'nm_product',
        'product_deskripsi',
        'qty',
        'product_price',
        'notes'
    ];
}
