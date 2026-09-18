<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsHdr extends Model
{
    use HasFactory;

    protected $table = 'tb_leads_hdr';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'code_leads',
        'id_customers',
        'notes',
        'kurs',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(LeadsItem::class, 'lead_id', 'id');
    }

    public function visits()
    {
        return $this->hasMany(LeadsVisit::class, 'lead_id', 'id');
    }
}
