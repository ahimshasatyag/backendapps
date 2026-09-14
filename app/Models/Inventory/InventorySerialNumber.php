<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventorySerialNumber extends Model
{
    use HasFactory;

    protected $table = 'inventory_serial_number';
    
    const CREATED_AT = 'date_create';
    const UPDATED_AT = null; // Legacy doesn't seem to have date_update

    protected $fillable = [
        'asset_id',
        'name_sn',
        'serial_number'
    ];

    public function asset()
    {
        return $this->belongsTo(InventoryAsset::class, 'asset_id');
    }
}
