<?php

namespace App\Models\po;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoHdr extends Model
{
    use HasFactory;

    protected $table = 'tb_po_hdr';
    protected $primaryKey = 'id_po';
    public $timestamps = false;

    protected $fillable = [
        'code_po',
        'code_quotation',
        'date_po',
        'status_po',
        'date_schdl',
        'id_suppliers',
        'nm_suppliers',
        'id_gudang',
        'id_mata_uang',
        'partner_ref',
        'notes',
        'amount_total',
        'id_product_lokasi',
        'date_create',
        'link_file'
    ];
}
