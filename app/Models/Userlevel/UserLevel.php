<?php

namespace App\Models\Userlevel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    use HasFactory;

    protected $table = 'm_users_level';
    protected $primaryKey = 'id_users_level';
    public $timestamps = false;

    protected $fillable = [
        'id_users_level',
        'nm_users_level',
        'id_dashboard',
        'date_create',
        'date_update'
    ];

    public function roles()
    {
        return $this->hasMany(UserRole::class, 'id_users_level', 'id_users_level');
    }
}
