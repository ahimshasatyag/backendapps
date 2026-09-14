<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBrand extends Model
{
    use HasFactory;

    protected $table = 'm_product_brand';
    protected $primaryKey = 'id_product_brand';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'id_product_brand',
        'nm_product_brand'
    ];
}
