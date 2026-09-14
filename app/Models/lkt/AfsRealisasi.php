<?php

namespace App\Models\lkt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfsRealisasi extends Model
{
    use HasFactory;

    protected $table = 'tb_afs_realisasi';
    protected $primaryKey = 'lkt_sub_code';
    
    public $timestamps = false;

    protected $fillable = [
        'id_afs_lkt',
        'actual_starting_date',
        'actual_day',
        'actual_service_amount',
        'actual_transport_amount',
        'actual_accommodation_amount',
        'actual_description',
        'actual_tot_detail_amount',
        'status',
        'f_cancel',
        'image',
        'actual_training',
        'actual_bongkar',
        'actual_transport',
        'flag_daring',
        'lap_penyelesain',
        'hasil_realisasi',
        'close_date',
        'progress',
        'update_notes',
        'after_image',
        'submit_date',
        'last_update',
        'alasan_cancel',
    ];
}
