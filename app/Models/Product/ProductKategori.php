<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductKategori extends Model
{
    use HasFactory;

    protected $table = 'm_product_kategori';
    protected $primaryKey = 'id_product_kategori';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';
}
