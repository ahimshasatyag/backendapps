<?php

namespace App\Models\customerinvoice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerInvoice extends Model
{
    use HasFactory;

    protected $table = 'tb_invoice_hdr';
    protected $primaryKey = 'id_invoice';

    public $timestamps = false;

    protected $guarded = [];

    public function details()
    {
        return $this->hasMany(CustomerInvoiceDetail::class, 'id_invoice', 'id_invoice');
    }
}
