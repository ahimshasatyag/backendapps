<?php

namespace App\Models\productpricelog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceLog extends Model
{
    use HasFactory;

    protected $table = 'm_product_price_history_search';
    public $timestamps = false;
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'id_product',
        'username',
        'date_create'
    ];
}
