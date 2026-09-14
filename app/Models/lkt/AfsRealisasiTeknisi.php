<?php

namespace App\Models\lkt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfsRealisasiTeknisi extends Model
{
    use HasFactory;

    protected $table = 'tb_afs_realisasi_teknisi';
    protected $primaryKey = 'id_afs_realisasi_teknisi';
    
    public $timestamps = false;

    protected $fillable = [
        'id_afs_cst',
        'id_afs_lkt',
        'lkt_sub_code',
        'id_karyawan',
        'actual_id_karyawan',
        'kpi',
        'f_cancel',
    ];
}
