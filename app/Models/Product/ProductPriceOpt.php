<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceOpt extends Model
{
    use HasFactory;

    protected $table = 'm_product_price_opt';
    protected $primaryKey = 'id_product_price_opt';
    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'nm_product_opt',
        'amount',
        'f_cancel'
    ];
}
