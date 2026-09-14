<?php

namespace App\Models\Quotations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationDetail extends Model
{
    use HasFactory;

    protected $table = 'tb_so_dtl';
    // protected $primaryKey = 'id'; // Or whatever your primary key is

    // If your table has date_create/date_update or created_at/updated_at
    // const CREATED_AT = 'date_create';
    // const UPDATED_AT = 'date_update';

    protected $guarded = [];

    public function header()
    {
        return $this->belongsTo(Quotation::class, 'id_so', 'id_so');
    }
}
