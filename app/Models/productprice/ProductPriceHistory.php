<?php

namespace App\Models\productprice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'm_product_price_history';
    public $timestamps = false; // Karena menggunakan date_create dan date_update

    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'waktu',
        'status',
        'id_product',
        'product_price',
        'kurs_bank',
        'username',
        'date_create',
        'delivery_term',
        'flag_active',
        'date_update',
        'product_price_agent',
        'disc_max_sales',
        'disc_max_manager'
    ];
}
