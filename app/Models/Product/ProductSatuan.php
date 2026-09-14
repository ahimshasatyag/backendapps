<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSatuan extends Model
{
    use HasFactory;

    protected $table = 'm_product_satuan';
    protected $primaryKey = 'id_product_satuan';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';
}
