<?php

namespace App\Models\Quotations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $table = 'tb_so_hdr';
    protected $primaryKey = 'id_so';

    // If your table has date_create/date_update or created_at/updated_at
    // const CREATED_AT = 'date_create';
    // const UPDATED_AT = 'date_update';

    protected $guarded = [];

    public function details()
    {
        return $this->hasMany(QuotationDetail::class, 'id_so', 'id_so');
    }

    public function options()
    {
        return $this->hasMany(QuotationDetailOption::class, 'id_so', 'id_so');
    }
}
