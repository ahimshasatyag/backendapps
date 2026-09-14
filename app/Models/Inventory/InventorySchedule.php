<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventorySchedule extends Model
{
    use HasFactory;

    protected $table = 'inventory_schedule';
    
    const CREATED_AT = 'date_create';
    const UPDATED_AT = null;

    protected $fillable = [
        'asset_id',
        'name',
        'deskripsi',
        'periode',
        'due_date',
        'reminder'
    ];

    public function asset()
    {
        return $this->belongsTo(InventoryAsset::class, 'asset_id');
    }

    public function pics()
    {
        return $this->hasMany(InventorySchedulePic::class, 'inventory_schedule_id');
    }
}
