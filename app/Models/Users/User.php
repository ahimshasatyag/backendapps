<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $table = 'm_users';
    
    // Using username as the primary key as in Cform.php
    protected $primaryKey = 'username';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'username',
        'password',
        'nm_users',
        'id_users_level',
        'is_active',
        'date_create',
        'date_update',
    ];

    protected $hidden = [
        'password',
    ];

    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'id_users_level', 'id_users_level');
    }
}
