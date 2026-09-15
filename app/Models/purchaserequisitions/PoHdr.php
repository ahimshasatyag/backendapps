<?php

namespace App\Models\purchaserequisitions;

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
        'date_po',
        'status_po'
    ];
}
