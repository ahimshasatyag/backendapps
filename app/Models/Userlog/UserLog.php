<?php

namespace App\Models\Userlog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    use HasFactory;

    protected $table = 'm_users_log';
    public $timestamps = false;
    
    protected $fillable = [
        'username',
        'activity',
        'ip_address',
        'd_createdate',
    ];
}
