<?php

namespace App\Models\So;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class So extends Model
{
    use HasFactory;

    protected $table = 'tb_so_hdr';
    protected $primaryKey = 'id_so';

    public $timestamps = false; // Assuming no created_at/updated_at by default unless specified

    protected $guarded = [];

    public function details()
    {
        return $this->hasMany(SoDetail::class, 'id_so', 'id_so');
    }

    public function options()
    {
        return $this->hasMany(SoDetailOption::class, 'id_so', 'id_so');
    }
}
