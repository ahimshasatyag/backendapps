<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsVisit extends Model
{
    use HasFactory;

    protected $table = 'tb_leads_visit';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'date_visit',
        'visit_activity',
    ];
}
