<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'm_notifikasi';
    protected $primaryKey = 'id_notifikasi';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'id_users_level',
        'kode_trans',
        'judul',
        'pesan',
        'action',
        'is_read',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\Auth\User::class, 'user_id', 'id');
    }

    public function userLevel()
    {
        return $this->belongsTo(\App\Models\Userlevel\UserLevel::class, 'id_users_level', 'id_users_level');
    }
}
