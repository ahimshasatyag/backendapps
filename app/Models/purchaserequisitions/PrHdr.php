<?php

namespace App\Models\purchaserequisitions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrHdr extends Model
{
    use HasFactory;

    protected $table = 'tb_pr_hdr';
    protected $primaryKey = 'id_pr';
    public $timestamps = false; // Assuming no standard laravel timestamps based on CI code

    protected $fillable = [
        'code_pr',
        'username',
        'date_request',
        'date_deadline',
        'status_pr'
    ];
}
