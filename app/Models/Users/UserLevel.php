<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    use HasFactory;

    protected $table = 'm_users_level';
    protected $primaryKey = 'id_users_level';
    public $timestamps = false;

    protected $fillable = [
        'nm_users_level',
    ];
}
