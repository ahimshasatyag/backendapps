<?php

namespace App\Models\customerinvoice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerInvoiceDetail extends Model
{
    use HasFactory;

    protected $table = 'tb_invoice_dtl';
    protected $primaryKey = 'id_invoice_dtl';

    public $timestamps = false;

    protected $guarded = [];

    public function invoice()
    {
        return $this->belongsTo(CustomerInvoice::class, 'id_invoice', 'id_invoice');
    }
}
