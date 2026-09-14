<?php

namespace App\Models\Inventorytype;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryType extends Model
{
    use HasFactory;

    protected $table = 'inventory_type';
    
    // Customize timestamp columns to match legacy CodeIgniter structure
    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'name'
    ];
}
