<?php

namespace App\Models\productpricereq;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product\Product;

class ProductPriceReq extends Model
{
    use HasFactory;

    protected $table = 'm_product_price_req';
    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'product_price_req',
        'product_price_acc',
        'username',
        'status',
        'f_kirim'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
