<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventorySchedulePic extends Model
{
    use HasFactory;

    protected $table = 'inventory_schedule_pic';
    
    const CREATED_AT = 'date_create';
    const UPDATED_AT = null;

    protected $fillable = [
        'inventory_schedule_id',
        'username'
    ];

    public function schedule()
    {
        return $this->belongsTo(InventorySchedule::class, 'inventory_schedule_id');
    }
}
