<?php

namespace App\Models\InventoryCategory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    use HasFactory;

    protected $table = 'inventory_category';
    
    // Customize timestamp columns to match legacy CodeIgniter structure
    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'name'
    ];
}
