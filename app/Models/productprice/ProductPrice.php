<?php

namespace App\Models\productprice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product\Product;

class ProductPrice extends Model
{
    use HasFactory;

    protected $table = 'm_product_price';
    protected $primaryKey = 'id_product';
    public $incrementing = false;
    protected $keyType = 'int';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'id_product',
        'product_price',
        'product_price_agent',
        'kurs_bank',
        'delivery_term',
        'username',
        'flag_active'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function history()
    {
        return $this->hasMany(ProductPriceHistory::class, 'id_product', 'id_product')->orderBy('waktu', 'desc');
    }

    public function options()
    {
        return $this->hasMany(ProductPriceOpt::class, 'id_product', 'id_product')->where('f_cancel', 0);
    }
}
