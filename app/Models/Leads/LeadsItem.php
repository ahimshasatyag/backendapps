<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsItem extends Model
{
    use HasFactory;

    protected $table = 'tb_leads_item';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'id_product',
        'qty',
        'product_price',
        'persentase',
    ];
}
