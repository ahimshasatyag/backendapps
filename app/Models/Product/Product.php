<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'm_product';
    protected $primaryKey = 'id_product';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'code_product',
        'nm_product',
        'product_deskripsi',
        'id_product_kategori',
        'id_product_sub_kategori',
        'id_product_satuan',
        'id_product_brand',
        'product_refference',
        'link_brosur',
        'link_foto',
        'flag_active'
    ];

    public function kategori()
    {
        return $this->belongsTo(ProductKategori::class, 'id_product_kategori', 'id_product_kategori');
    }

    public function subKategori()
    {
        return $this->belongsTo(ProductSubKategori::class, 'id_product_sub_kategori', 'id_product_sub_kategori');
    }

    public function satuan()
    {
        return $this->belongsTo(ProductSatuan::class, 'id_product_satuan', 'id_product_satuan');
    }

    public function brand()
    {
        return $this->belongsTo(ProductBrand::class, 'id_product_brand', 'id_product_brand');
    }

    public function options()
    {
        return $this->hasMany(ProductPriceOpt::class, 'id_product', 'id_product')->where('f_cancel', '0');
    }
}
