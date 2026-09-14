<?php

namespace App\Models\csr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfsCsr extends Model
{
    use HasFactory;

    protected $table = 'tb_afs_csr';
    protected $primaryKey = 'id_afs_csr';
    
    // Disable timestamps if not used by default in this old table
    public $timestamps = false;

    protected $fillable = [
        'csr_code',
        'csr_date',
        'id_customers',
        'id_karyawan',
        'barcode',
        'do_code',
        'waranty_start',
        'waranty_time',
        'waranty_end',
        'lap_kerusakan',
        'id_product',
        'lokasi',
        'csr_input_date',
        'csr_by',
        'csr_status',
        'sts_pasang',
        'mesin_lama',
        'image',
        'approved_csr_by',
        'csr_approve_date',
        'f_cancel',
        'alasan_cancel',
        'csr_cancel_date'
    ];
}
