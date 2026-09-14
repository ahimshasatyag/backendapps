<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryCategory\InventoryCategory;
use App\Models\Inventorytype\InventoryType;

class InventoryAsset extends Model
{
    use HasFactory;

    protected $table = 'inventory_assets';
    
    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'name',
        'inventory_type_id',
        'inventory_category_id',
        'procured_date',
        'purchased_date',
        'deskripsi',
        'serial',
        'status',
        'f_print'
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function type()
    {
        return $this->belongsTo(InventoryType::class, 'inventory_type_id');
    }

    public function serialNumbers()
    {
        return $this->hasMany(InventorySerialNumber::class, 'asset_id');
    }

    public function schedules()
    {
        return $this->hasMany(InventorySchedule::class, 'asset_id');
    }
}
