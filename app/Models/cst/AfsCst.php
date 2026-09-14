<?php

namespace App\Models\cst;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfsCst extends Model
{
    use HasFactory;

    protected $table = 'tb_afs_cst';
    protected $primaryKey = 'id_afs_cst';
    
    // Disable timestamps if not used by default
    public $timestamps = false;

    protected $fillable = [
        'id_afs_csr',
        'cst_code',
        'cst_date',
        'status',
        'cst_approve_date',
        'approved_cst_by',
        'cst_done_date',
        'done_cst_by',
        'cst_ignore_date',
        'ignore_cst_by',
    ];
}
