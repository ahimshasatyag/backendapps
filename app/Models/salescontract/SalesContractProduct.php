<?php

namespace App\Models\salescontract;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesContractProduct extends Model
{
    use HasFactory;

    protected $table = 'tb_sales_contract_product';

    public $timestamps = false;

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product\Product::class, 'id_product', 'id_product');
    }

    public function salesContract()
    {
        return $this->belongsTo(\App\Models\salescontract\SalesContract::class, 'id_sales_contract', 'id_sales_contract');
    }
}
