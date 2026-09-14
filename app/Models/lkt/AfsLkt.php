<?php

namespace App\Models\lkt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfsLkt extends Model
{
    use HasFactory;

    protected $table = 'tb_afs_lkt';
    protected $primaryKey = 'id_afs_lkt';
    
    public $timestamps = false;

    protected $fillable = [
        'id_afs_cst',
        'lkt_code',
        'starting_date',
        'estimation_day',
        'description',
        'service_amount',
        'transport_amount',
        'accommodation_amount',
        'tot_detail_amount',
        'actual_transport',
        'image',
        'flag_done',
        'f_cancel',
        'bast',
        'lkt_done_date',
        'lkt_cancel_date'
    ];
}
