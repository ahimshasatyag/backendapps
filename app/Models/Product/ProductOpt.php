<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOpt extends Model
{
    use HasFactory;

    protected $table = 'm_product_opt';
    protected $primaryKey = 'id_product_opt';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'nm_product_opt'
    ];
}
