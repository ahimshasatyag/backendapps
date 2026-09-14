<?php

namespace App\Models\productsn;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product\Product;

class ProductSn extends Model
{
    use HasFactory;

    protected $table = 'm_product_sn';
    protected $primaryKey = 'id_product_sn';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'id_product',
        'sn',
        'nqty'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
