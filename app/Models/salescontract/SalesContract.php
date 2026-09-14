<?php

namespace App\Models\salescontract;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesContract extends Model
{
    use HasFactory;

    protected $table = 'tb_sales_contract_hdr';
    protected $primaryKey = 'id_sales_contract';

    public $timestamps = false;

    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(\App\Models\salescontract\SalesContractProduct::class, 'id_sales_contract', 'id_sales_contract');
    }

    public function so()
    {
        return $this->hasOne(\App\Models\So\So::class, 'id_sales_contract', 'id_sales_contract');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customers\Customer::class, 'id_customers', 'id_customers');
    }
}
