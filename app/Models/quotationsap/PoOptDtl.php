<?php

namespace App\Models\quotationsap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoOptDtl extends Model
{
    use HasFactory;

    protected $table = 'tb_po_opt_dtl';
    protected $primaryKey = 'id_po_opt_dtl';
    public $timestamps = false;

    protected $fillable = [
        'id_po_dtl',
        'id_product',
        'id_po',
        'nm_product_opt',
        'harga'
    ];
}
