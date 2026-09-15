<?php

namespace App\Models\suppliers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'm_suppliers';
    protected $primaryKey = 'id_suppliers';
    
    // Assuming id_suppliers could be a string if it uses runningnumber, or integer.
    // If it's a string, we set incrementing to false.
    public $incrementing = false;
    protected $keyType = 'string';
    
    public $timestamps = false;
    protected $guarded = [];
}
