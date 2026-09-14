<?php

namespace App\Models\Do;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoHdr extends Model
{
    use HasFactory;

    protected $table = 'tb_do_hdr';
    protected $primaryKey = 'id_do';

    public $timestamps = false;

    protected $guarded = [];

    public function details()
    {
        return $this->hasMany(DoDtl::class, 'id_do', 'id_do');
    }
}
