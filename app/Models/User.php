<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Aman untuk kolom yang kamu pakai
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'name',         // ada / tidak ada di DB tidak masalah
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
