<?php

namespace App\Models\incshipment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingHdr extends Model
{
    use HasFactory;

    protected $table = 'tb_incoming_hdr';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'id_po',
        'id_suppliers',
        'status_incoming',
        'date_receive',
        'date_create',
        'date_update',
        'f_assign_barcode',
        'f_print_barcode',
        'f_ok_receive'
    ];
}
