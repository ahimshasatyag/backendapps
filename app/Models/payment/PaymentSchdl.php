<?php

namespace App\Models\payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSchdl extends Model
{
    use HasFactory;

    protected $table = 'tb_payment_schdl';
    protected $primaryKey = 'id_payment_schdl';
    public $timestamps = false;

    protected $guarded = [];
}
