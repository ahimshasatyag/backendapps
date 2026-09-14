<?php

namespace App\Models\Userlevel;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $table = 'm_users_role';
    public $timestamps = false;

    protected $fillable = [
        'id_menu',
        'id_users_power',
        'id_users_level'
    ];
}
